# Arquitetura – PayB2

Este documento descreve as decisões de arquitetura e os porquês por trás do desenho do PayB2, uma API de Transferências Financeiras.
## Visão Geral
- Objetivo: fluxo de transferência robusto e observável, sem acoplamento indevido ao domínio.
- Diretrizes: camadas estritas (Domain, Application, Infrastructure), observabilidade nas bordas, testes fáceis e isolamento de regras de negócio.

## Padrão Arquitetural
Adotamos **Clean Architecture** com separação clara de responsabilidades:

- **Domain**
  - Conteúdo: entidades (`User`, `Wallet`, `Transfer`), VOs (`Money`, `Email`, `Document`), enums (`UserType`, `TransferStatus`), exceções e serviços de domínio.
  - Princípio: nenhuma dependência de framework. O domínio é puro e testável.

- **Application**
  - Conteúdo: casos de uso, orquestração de portas (repositories/services), transação e publicação de eventos.
  - Exemplo: `PerformTransfer` valida, autoriza, executa transação (locks pessimistas), persiste, e publica `TransferCompletedEvent`.

- **Infrastructure**
  - Conteúdo: adapters de I/O (Doctrine Repositories, Symfony HTTP, Messenger, observabilidade), controllers, listeners, providers.
  - Limite: toda a dependência de framework, IO e observabilidade vive aqui.

### Por que esta separação
- Facilita evolução: domínio independente de detalhes técnicos.
- Melhora testabilidade: regras de negócio testadas sem subir stack.
- Evita “code smell” de logs/métricas/IO no core do caso de uso.

## Observabilidade
### Objetivo
Capturar **tracing, métricas e logs** sem poluir o domínio e com impacto mínimo.

### Estratégia
- **Decorator Pattern**: `TransferObservabilityDecorator` intercepta o `PerformTransfer` na camada de Infrastructure.
  - Uso: medir início/fim, latência, erros.
  - Benefício: separação de preocupações; o domínio não conhece OTel/Prometheus/Loki.

- **OpenTelemetry (OTel)**
  - Instrumentação nas bordas: HTTP (`RequestTracingSubscriber`), Messenger (`TracingMiddleware`), HttpClient (`TracingClient`).
  - Provider: `TracerProviderInterface` injetado via DI; domínio continua agnóstico.

- **Métricas**
  - Em listeners/decorators, contadores/histogramas via `MetricsInterface` (Prometheus).
  - Endpoint `/metrics` exporta o `CollectorRegistry`.

- **Logs**
  - Estruturados, com `correlation_id` adicionados no adapter de logger, concentrados em listeners HTTP.
  - Coleta com Promtail → Loki; consulta via Grafana Explore.

### Por que Decorator em vez de inline
- Inline (logs/métricas dentro de use case) aumenta acoplamento e ruído.
- Decorator permite trocar estratégia de observabilidade sem tocar no core.

## Pipeline de Tracing
- Exportação: **OTLP HTTP** → **Tempo** (endpoint de ingestão: `http://tempo:4318`).
- Visualização: **Grafana** com datasources provisionados (Tempo/Prometheus/Loki).
- Rede Docker: Grafana/Tempo/Prometheus/Loki e a app compartilham a mesma rede (`backend`), permitindo URL internas (ex.: `http://tempo:4318`).

### Por que via Factory
- Permite inicializar exporter/processors com env vars (`OTEL_EXPORTER_OTLP_ENDPOINT=http://tempo:4318`, `OTEL_SERVICE_NAME=payb2-api`).
- Evita criar providers “na mão” no kernel; DI mantém testabilidade e substituibilidade.

## Mensageria e Transações
- Mensageria: Symfony Messenger com transport assíncrono, e failed transport para retries.
- Transações: gerenciadas por `TransactionManagerInterface` com locks pessimistas em wallets, evitando race conditions.

## Conclusão
A arquitetura privilegia clareza e isolamento: domínio puro, aplicação orquestradora e infraestrutura como borda. Observabilidade é aplicada com Decorator e OTel nas bordas, mantendo baixo acoplamento e alta visibilidade operativa. O pipeline em Docker (Tempo/Prometheus/Loki/Grafana) facilita debug e monitoração sem penalizar o core.

