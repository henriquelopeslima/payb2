# Comandos do Makefile

Tabela de comandos disponíveis para facilitar operações de desenvolvimento, testes e manutenção do projeto.

| Alvo (make <target>) | Descrição | Comando efetivo |
|---|---|---|
| up | Sobe toda a stack Docker em background | `docker compose up -d` |
| stop | Para os serviços da stack Docker | `docker compose stop` |
| down | Derruba a stack e remove volumes e órfãos (todas profiles) | `docker compose --profile '*' down --volumes --remove-orphans` |
| container_php | Abre um shell dentro do container PHP | `docker compose exec php sh` |
| run_command | Executa um comando arbitrário no container PHP (use CMD="...") | `docker compose exec -T php sh -c "$(CMD)"` |
| install_dependencies | Instala dependências Composer com autoloader otimizado | `composer install --optimize-autoloader` via `make run_command` |
| migrations | Executa migrations do Doctrine | `bin/console doctrine:migrations:migrate --no-interaction` via `make run_command` |
| fixtures | Carrega fixtures de dados | `bin/console doctrine:fixture:load --no-interaction` via `make run_command` |
| test | Roda a suíte de testes PHPUnit | `bin/phpunit` via `make run_command` |
| lint | Aplica formatação com PHP-CS-Fixer (modifica arquivos) | `php vendor/bin/php-cs-fixer fix --diff` via `make run_command` |
| lint-dry-run | Checa formatação com PHP-CS-Fixer (sem modificar) | `php vendor/bin/php-cs-fixer fix --diff --dry-run` via `make run_command` |
| copy_dist_files | Copia arquivos de configuração de dist para locais | `cp .php-cs-fixer.dist.php .php-cs-fixer.php && cp phpunit.dist.xml phpunit.xml` |
| setup | Pipeline de setup completo (up + install + copy + migrate + fixtures) | Alias: `up install_dependencies copy_dist_files migrations fixtures` |

Notas:
- A variável `CMD` deve ser fornecida ao alvo `run_command` quando usada diretamente: `make run_command CMD="echo hello"`.
- O alvo `setup` é recomendado no primeiro uso para preparar o ambiente de desenvolvimento.

