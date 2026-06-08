# Bolão da Copa do Mundo 2026 ⚽

Bolão de palpites entre amigos para a Copa do Mundo FIFA 2026. Cada participante
dá o palpite do placar de cada jogo; o sistema pontua os acertos e monta o ranking.
Mobile-first, em **PHP 8 + SQLite**, sem framework e sem build step.

- 🔐 Cadastro só por **link de convite** (sem e-mail/SMTP).
- 🗓️ Todos os **104 jogos** carregados da **API oficial da FIFA**.
- 🎯 Palpites **travam no apito inicial** (horário de Brasília); mata-mata libera quando os times são definidos.
- ⭐ **Palpite de campeão**: campeão, vice e 3º lugar (pontos extras, travam no início da Copa).
- 👥 **Ligas separadas**: liga "Geral" automática + ligas próprias com link de convite. Os palpites valem em todas as ligas.
- 🏆 Ranking por liga, automático, com desempates.
- 🤖 **Cron** atualiza placares e recalcula a pontuação sozinho.

## Pontuação (padrão, configurável no Admin)

| Acerto | Pontos |
|---|---|
| Placar exato | **5** |
| Vencedor **+ saldo de gols** (jogos com vencedor) | **3** |
| Só o resultado (vitória/empate/derrota) | **1** |
| Errou | 0 |

> Empate com placar errado (ex.: palpitou 2×2, deu 1×1) conta como **"acertou o resultado" = 1 ponto**
> (convenção comum: o saldo de empate é sempre 0). Para pontuar o placar, usa-se o resultado do
> **tempo normal**; pênaltis só decidem o classificado, não entram no palpite.

**Bônus de torneio** (padrão, configurável): campeão **+20**, vice **+10**, 3º lugar **+7**.
Travam no 1º jogo da Copa e são pontuados pela final e pela disputa de 3º lugar (o campeão/3º
considera quem avançou, inclusive em pênaltis). Somam ao total no ranking.

## Ligas

Os **palpites são únicos por pessoa** (você palpita cada jogo uma vez) — a *liga* é só um
agrupamento de ranking, então dá para participar de várias ao mesmo tempo.

- Todos entram automaticamente na liga **"Geral"** (o ranking geral).
- Qualquer participante pode **criar uma liga** em *Ligas* e compartilhar o **link** (ou código).
- Abrir um link de liga (`/liga/{código}`) entra direto; quem não tem conta cria na hora e já entra.

## Requisitos

- PHP 8.1+ com extensões `pdo_sqlite`, `curl`, `json` (testado em PHP 8.5).
- Composer.
- Acesso a crontab no servidor (para atualização automática).

## Instalação

```bash
composer install

# Cria o banco, semeia as configurações e o 1º admin:
php db/migrate.php --admin-name="Seu Nome" --admin-email="voce@exemplo.com" --admin-pass="suaSenhaForte"

# Importa os 104 jogos da FIFA (primeira carga):
php bin/sync.php
```

Reexecutar `migrate.php` com os mesmos dados é seguro e **atualiza a senha** do admin.

### Rodar localmente (teste)

```bash
php -S localhost:8000 -t public public/index.php
# abra http://localhost:8000
```

## Deploy no servidor

1. Suba os arquivos e rode `composer install --no-dev` no servidor.
2. **Aponte o document root do site para a pasta `public/`** (importante: o banco e as
   configurações ficam fora dela e não devem ser acessíveis pela web).
3. Garanta que `storage/` é gravável pelo PHP (`chmod -R 775 storage`).
4. Rode `php db/migrate.php ...` e `php bin/sync.php` uma vez.

Já existem `.htaccess` para Apache (rewrite + bloqueio de arquivos sensíveis). No **Nginx**:

```nginx
root /caminho/bolao/public;
index index.php;
location / { try_files $uri /index.php?$query_string; }
location ~ \.php$ { include fastcgi_params; fastcgi_pass unix:/run/php/php-fpm.sock; fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; }
location ~* \.(sqlite|sql|log)$ { deny all; }
```

## Cron (atualização automática)

Adicione ao crontab (ajuste o caminho do PHP e do projeto):

```cron
# A cada 10 minutos, sem sobreposição de execuções
*/10 * * * * /usr/bin/flock -n /tmp/bolao-sync.lock /usr/bin/php /caminho/bolao/bin/sync.php >> /caminho/bolao/storage/logs/sync.log 2>&1
```

Durante os jogos dá para deixar mais frequente, ex.: `*/2 13-23 * * *`.
O sync também pode ser disparado pelo botão **"Sincronizar agora"** em *Admin → Configurações*.

## Como usar

1. Entre com o admin em `/login`.
2. Em **Admin → Configurações → Convites**, gere um link e mande no grupo dos amigos.
3. Cada amigo abre o link, cria a conta e começa a palpitar em **Jogos**.
4. Os placares chegam pelo cron; o **Ranking** atualiza sozinho.

## Configuração

`config/config.php` (sobrescrevível por variáveis de ambiente `APP_ENV`, `APP_URL`):

- `APP_ENV=production` esconde erros (padrão). Use `dev` para depurar.
- `APP_URL` só é necessário se o app rodar em subpasta.
- Os IDs da FIFA (competição `17`, temporada `285023` = Copa 2026) ficam em `config.php`
  e também em `settings`.

## Estrutura

```
public/      → web root (front controller + assets)
app/         → código (classes, controllers, repositories, views)
bin/sync.php → entrada do cron
db/          → schema.sql + migrate.php
config/      → config.php
storage/     → bolao.sqlite + logs (fora do web root)
```

## Fonte de dados

API pública oficial da FIFA:
`https://api.fifa.com/api/v3/calendar/matches?idCompetition=17&idSeason=285023&count=300&language=en`
— sem chave de API. Todo o parsing está isolado em `app/FifaClient.php`; se a FIFA mudar o
formato, só esse arquivo precisa de ajuste.

## Segurança

Senhas com `password_hash`, sessões endurecidas, CSRF em todo POST, prepared statements,
travas de palpite e mata-mata validadas **no servidor**, e arquivos sensíveis fora do web root.
