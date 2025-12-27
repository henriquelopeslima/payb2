# Contexto de Domínio – Sistema de Transferências Financeiras

Este documento apresenta a visão de domínio e o desenho de solução para o sistema de Transferências Financeiras (PHP 8 / Symfony), seguindo Clean Architecture e DDD.

## Espaço do Problema (O "Que")
### Caso de Uso: Perform Transfer
Um usuário (Payer) envia dinheiro para outro usuário (Payee). O sistema deve:
- Validar identidades (Payer/Payee) e permissões (tipo de usuário apto a enviar).
- Verificar saldo suficiente no Wallet do Payer.
- Executar a operação de débito/crédito de forma **atômica** (transação única, com locks pessimistas) para evitar condições de corrida.
- Persistir a Transferência e publicar evento de conclusão.

### Regras de Negócio
- Um usuário não pode transferir para si mesmo.
- Apenas usuários do tipo “comum” podem enviar (ex.: lojistas não enviam).
- A transferência só ocorre se o **Wallet** do Payer tiver saldo suficiente.
- Transação deve ser **atômica**: débito no Wallet do Payer e crédito no Wallet do Payee acontecem no mesmo contexto transacional.
- Em caso de falha (autorização externa, saldo, validação), a operação é abortada e nenhum estado parcial é persistido.

### Linguagem Ubíqua
- **Wallet**: carteira associada ao usuário, com saldo em dinheiro.
- **Payer**: quem envia o dinheiro.
- **Payee**: quem recebe o dinheiro.
- **Transaction**: operação atômica de débito/crédito de valores entre duas carteiras.
- **Authorization**: verificação externa (serviço de autorização) que permite ou nega a transferência.
- **Transfer**: entidade de domínio que registra uma tentativa/operação de transferência com status.

## Modelagem da Solução (O "Como")
### Clean Architecture + DDD
- **Domain**: entidades, VOs, enums e regras. Sem dependência de framework.
- **Application**: orquestra use cases e portas (repositórios/serviços). Ex.: `PerformTransfer` valida, autoriza, usa transação, persiste, publica evento.
- **Infrastructure**: adapters (HTTP, Doctrine, Messenger, Observabilidade).

### Decorator de Observabilidade
Utilizamos o **Decorator Pattern** (`TransferObservabilityDecorator`) para encapsular o use case `PerformTransfer`. Objetivos:
- Adicionar **tracing OpenTelemetry** (spans de início/fim, latência, erro) sem inserir lógica de observabilidade no **núcleo de negócio**.
- Preservar **Separation of Concerns**: o caso de uso continua puro e independente de infraestrutura.
- Permitir evolução da observabilidade (OTel, Prometheus, Loki) sem tocar em Domain/Application.

Benefícios:
- Baixo acoplamento, alta testabilidade.
- Observabilidade consistente e centralizada na borda (Infrastructure).

## Modelagem Visual
### Diagrama de Classes (Domain)
```mermaid
classDiagram
  direction LR

  %% Value Objects
  class Money {
    +amount: Decimal
    +currency: string
    +add(m: Money) Money
    +subtract(m: Money) Money
    +isPositive(): bool
  }

  class Email {
    +value: string
  }

  class Document {
    +value: string
  }

  class PasswordHash {
    +value: string
  }

  %% Enums
  class UserType {
    <<enumeration>>
    +COMMON
    +MERCHANT
  }

  class TransferStatus {
    <<enumeration>>
    +PENDING
    +COMPLETED
    +FAILED
  }

  %% Entities
  class User {
    +id: Uuid
    +name: string
    +email: Email
    +document: Document
    +type: UserType
    +passwordHash: PasswordHash
    +equals(other: User): bool
  }

  class Wallet {
    +id: Uuid
    +userId: Uuid
    +balance: Money
    +credit(amount: Money): void
    +debit(amount: Money): void
  }

  class Transfer {
    +id: Uuid
    +payerId: Uuid
    +payeeId: Uuid
    +amount: Money
    +status: TransferStatus
    +createdAt: DateTimeImmutable
    +complete(): void
    +fail(reason: string): void
  }

  %% Domain Service (simple)
  class MoneyTransferrerService {
    +transfer(payer: Wallet, payee: Wallet, amount: Money): void
  }

  %% Repositórios (contratos de domínio)
  class UserRepositoryInterface {
    +findById(id: Uuid): User
  }

  class WalletRepositoryInterface {
    +findByUserIdExclusiveLock(userId: Uuid): Wallet
    +save(wallet: Wallet): void
  }

  class TransferRepositoryInterface {
    +save(transfer: Transfer): void
  }

  %% Relacionamentos
  User "1" o-- "1" Wallet : owns
  Transfer --> Money : amount
  Wallet --> Money : balance
  User --> Email
  User --> Document
  User --> PasswordHash
  User --> UserType
  Transfer --> TransferStatus
  MoneyTransferrerService ..> Wallet : uses
  MoneyTransferrerService ..> Money : uses
  WalletRepositoryInterface ..> Wallet
  UserRepositoryInterface ..> User
  TransferRepositoryInterface ..> Transfer
```

### Diagrama de Classes
```mermaid
classDiagram
    direction LR

    class PerformTransferInterface {
      +__invoke(command: PerformTransferCommand) PerformTransferOutput
    }

    class PerformTransferService {
      +__invoke(command: PerformTransferCommand) PerformTransferOutput
      -userRepository: UserRepositoryInterface
      -walletRepository: WalletRepositoryInterface
      -transferRepository: TransferRepositoryInterface
      -authorizationService: TransferAuthorizationServiceInterface
      -transactionManager: TransactionManagerInterface
      -moneyTransferrer: MoneyTransferrerService
      -eventBus: EventBusInterface
    }

    class TransferObservabilityDecorator {
      +__invoke(command: PerformTransferCommand) PerformTransferOutput
      -inner: PerformTransferInterface
      -logger: LoggerPortInterface
      -metrics: MetricsPortInterface
      -tracerProvider: TracerProviderInterface
      -correlationIdProvider: CorrelationIdProviderInterface
    }

    PerformTransferInterface <|.. PerformTransferService
    PerformTransferInterface <|.. TransferObservabilityDecorator
    TransferObservabilityDecorator o--> PerformTransferInterface : decorates
```
> Como pode ver o Decorator implementa a mesma interface do serviço que decora, permitindo transparência na injeção de dependência.

### Diagrama de Sequência
```mermaid
sequenceDiagram
  autonumber
  participant Client as Client
  participant Decorator as TransferObservabilityDecorator
  participant Service as PerformTransferService

  Client->>Decorator: __invoke(PerformTransferCommand)
  Note over Decorator: Start Span (OTel), log start
  Decorator->>Service: __invoke(command)
  Service-->>Service: Validar regras, autorização externa
  Service-->>Service: Transação atômica (locks, débito/crédito)
  Service-->>Decorator: PerformTransferOutput
  Note over Decorator: End Span, métricas (latência/sucesso/erro)
  Decorator-->>Client: Response (Output)
```

## Contexto de Infraestrutura
### Pipeline de Observabilidade
- **OpenTelemetry SDK** cria spans atrelados às operações de aplicação (via Decorator e instrumentação nas bordas: HTTP/Messenger/HttpClient).
- **OTLP Exporter (HTTP)** envia os spans para **Grafana Tempo**: endpoint interno em Docker `http://tempo:4318` (ou `http://localhost:4318` em cenários locais).
- **Grafana** visualiza os traces (datasource Tempo) e correlaciona com métricas (Prometheus) e logs (Loki).

### Notas de Deploy (Docker Compose)
- App, Grafana, Tempo, Prometheus e Loki compartilham a rede `backend`.
- O Decorator permanece agnóstico ao provider: a DI liga `TracerProviderInterface` a um provider real (SDK) ou noop, conforme ambiente.

## Visualização com C4 Model (Níveis de Abstração)

### System Context
```mermaid
flowchart LR
  Client([Cliente / Aplicação Externa])
  PayB2[[PayB2 API]]
  AuthSvc[(Serviço de Autorização)]
  NotifySvc[(Serviço de Notificação)]
  Grafana[(Grafana)]
  Tempo[(Grafana Tempo)]
  Prom[(Prometheus)]
  Loki[(Loki)]

  Client -->|HTTP /transfer| PayB2
  PayB2 -->|HTTP| AuthSvc
  PayB2 -->|Async Event| NotifySvc
  PayB2 -->|/metrics| Prom
  PayB2 -->|Logs via Promtail| Loki
  PayB2 -->|OTLP Traces| Tempo
  Grafana -->|Visualiza| Prom
  Grafana -->|Visualiza| Loki
  Grafana -->|Visualiza| Tempo
```

### Containers
```mermaid
flowchart TB
  subgraph Docker Network: backend
    Nginx[Nginx]
    PHPFPM[PHP-FPM / Symfony]
    Postgres[(PostgreSQL)]
    Redis[(Redis)]
    PromSrv[Prometheus]
    LokiSrv[Loki]
    TempoSrv[Grafana Tempo]
    GrafanaSrv[Grafana]

    Nginx --> PHPFPM
    PHPFPM -- Doctrine ORM --> Postgres
    PHPFPM -- Cache/Queue --> Redis
    PHPFPM -- /metrics --> PromSrv
    PHPFPM -- OTLP --> TempoSrv
    PHPFPM -- Logs via Promtail --> LokiSrv
    GrafanaSrv --- PromSrv
    GrafanaSrv --- TempoSrv
    GrafanaSrv --- LokiSrv
  end
```

### Components (na aplicação)
```mermaid
flowchart LR
  Controller[TransferController]
  Decorator[TransferObservabilityDecorator]
  UseCase[PerformTransfer]
  Domain[Domain: User, Wallet, Transfer]
  Repositories[Repositories: Doctrine Adapters]
  TxManager[TransactionManager]
  AuthServicePort[Authorization Service Port]
  MessageBus[Message Bus]

  Controller --> Decorator
  Decorator --> UseCase
  UseCase --> Domain
  UseCase --> Repositories
  UseCase --> TxManager
  UseCase --> AuthServicePort
  UseCase --> MessageBus
```

## Diagrama de Caso de Uso (Visual)
Representação simplificada do caso de uso "Perform Transfer".

```mermaid
flowchart TB
  ActorPayer[Actor: Payer]
  ActorPayee[Actor: Payee]
  System[Sistema: PayB2]

  subgraph UC[Use Case: Perform Transfer]
    Step1[Enviar requisicao de transferencia]
    Step2[Validar identidades e regras do dominio]
    Step3[Autorizar externamente]
    Step4[Executar transacao atomica de debito e credito]
    Step5[Persistir e publicar evento]
    Step6[Retornar resultado]
  end

  ActorPayer --> Step1
  Step1 --> System
  System --> Step2
  Step2 --> Step3
  Step3 --> Step4
  Step4 --> Step5
  Step5 --> Step6
  Step6 --> ActorPayer
  Step5 --> ActorPayee
```

---
Este documento prioriza clareza de fronteiras e a justificativa das decisões. O resultado é um sistema com domínio limpo, orquestração clara e observabilidade robusta, aplicável a ambientes de produção com a stack Grafana Tempo/Prometheus/Loki.
