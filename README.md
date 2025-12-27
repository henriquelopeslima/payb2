# PayB2 – Clean Architecture, High Availability & Observability

[![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?logo=php)](https://www.php.net/)
[![Build](https://img.shields.io/badge/Build-CLI%20%2F%20Docker-blue?logo=docker)](docker-compose.yml)
[![Coverage](https://img.shields.io/badge/Coverage-PHPUnit%20%2F%20Xdebug-green?logo=php)](phpunit.xml)

API de pagamentos simplificada construída com foco em praticar um estudo de caso, em boas práticas.

## Tech Stack
- Linguagem/Framework: PHP 8.4, Symfony 8
- Persistência: Doctrine ORM 3, PostgreSQL
- Infra: Docker
- Mensageria: Symfony Messenger (assíncrono) com transport `async`
- Observabilidade: OpenTelemetry (tracing nas bordas), Prometheus (métricas), Grafana (dashboards), Loki (logs), Tempo (traces)

## Architecture Highlights
- Clean Architecture com 3 camadas:
  - Domain: entidades, VOs, enums, exceções e serviços de domínio (sem dependência de framework).
  - Application: casos de uso orquestram domínio + ports (ex.: `PerformTransfer`).
  - Infrastructure: adapters de I/O (HTTP, Doctrine, Messenger, observabilidade).
- Observabilidade desacoplada via Pattern Decorator (UseCase Decorator):
  - Observabilidade fora do core evita “code smell” (logs/métricas dentro de regras de negócio), mantendo clareza e baixo acoplamento.
  - Logs estruturados e métricas são aplicados por decorators/listeners, mantendo o core limpo e um infrastrutura desacoplada.
- Feature Flags (ponto de extensão): configuração e toggles no nível de infraestrutura sem invadir o domínio.

## Quick Start
Pré-requisitos: Docker e docker-compose.

```bash
# subir a stack
docker compose up -d

# instalar dependências PHP
docker compose exec -it php sh -lc "composer install"

# migrar e carregar dados de exemplo
docker compose exec -it php sh -lc "bin/console d:m:m --no-interaction"
docker compose exec -it php sh -lc "bin/console d:f:l --no-interaction"
```

Endpoints principais:
- HTTP API: `http://localhost/transfer` (POST) – ver `request/transfer.http`.
- Grafana UI: `http://localhost:3000` (datasources provisionados via `docker/grafana/provisioning`).

Observações de ambiente:
- Variáveis `.env` controlam DB (`DATABASE_URL`), URLs externas (autorização/notificação), Messenger DSN e Default Router URI.
- Stack de observabilidade usa datasources provisionados para evitar reconfiguração após `down/up`.

## Testing
Execute a suíte de testes com PHPUnit via Make:

```bash
make test
```

Ou diretamente:

```bash
docker compose exec -T php sh -lc "bin/phpunit"
```

## Observabilidade (pipeline resumido)
- Traces: OpenTelemetry exporta via OTLP HTTP para Tempo (`http://tempo:4318`).
- Visualização: datasources Grafana provisionados (Prometheus, Loki, Tempo).
- Métricas: registradas em listeners/decorators e expostas em `/metrics`.
- Logs: estruturados (correlation_id) e coletados via Promtail para Loki.

## DI e Autowiring de OTel (services.yaml)
- Registramos um `TracerProviderInterface` via factory para resolver autowire sem poluir o core:
  - Em ambientes com tracing real: `OpenTelemetry\SDK\Trace\TracerProvider` via factory (ex.: `Bootstrap::init()`), alias para `OpenTelemetry\API\Trace\TracerProviderInterface`.
  - Em ambientes sem OTel: `OpenTelemetry\API\Trace\NoopTracerProvider` e alias.
- O Decorator (`TransferObservabilityDecorator`) recebe `TracerProviderInterface` e permanece agnóstico ao provider concreto.

## Estrutura (alto nível)
- `src/Domain`: Entidades,VOs, enums, exceções e serviços.
- `src/Application`: casos de uso + ports.
- `src/Infrastructure`: adapters (HTTP/Doctrine/Messenger/Observability).
- `config/`: DI, pacotes, mapeamentos e observabilidade.
- `docker/`: Nginx, PHP, Prometheus, Grafana, Loki, Promtail, Tempo.
- `tests/`: unidade e serviços.
>Vale ressaltar que a estrutura detalhada está documentada em [ARCHITECTURE.md](ARCHITECTURE.md).

## Operação & Alta Disponibilidade
- Fluxo de transferência resiliente: validação de domínio, autorização externa, transação com locks pessimistas.
- Mensageria com failed transport e retentativas.
- Observabilidade consistente e desacoplada.

## Roadmap
- Refinar pipelines Promtail (promover `event`/`correlation_id` como labels Loki).
- Dashboards Grafana versionados e provisionados.
- Flags operacionais para modos de autorização/notificação.
