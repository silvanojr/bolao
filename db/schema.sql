-- Bolão Copa 2026 — schema SQLite
-- Datas/horários sempre em UTC ISO (datetime('now') = UTC no SQLite).

CREATE TABLE IF NOT EXISTS users (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    name          TEXT    NOT NULL,
    email         TEXT    NOT NULL,
    password_hash TEXT    NOT NULL,
    is_admin      INTEGER NOT NULL DEFAULT 0,
    created_at    TEXT    NOT NULL DEFAULT (datetime('now'))
);
CREATE UNIQUE INDEX IF NOT EXISTS idx_users_email ON users (lower(email));

CREATE TABLE IF NOT EXISTS invites (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    token       TEXT    NOT NULL,
    label       TEXT,                              -- nota livre ("turma do trabalho")
    created_by  INTEGER NOT NULL REFERENCES users(id),
    max_uses    INTEGER NOT NULL DEFAULT 0,         -- 0 = ilimitado
    used_count  INTEGER NOT NULL DEFAULT 0,
    expires_at  TEXT,                               -- NULL = nunca expira
    revoked     INTEGER NOT NULL DEFAULT 0,
    created_at  TEXT    NOT NULL DEFAULT (datetime('now'))
);
CREATE UNIQUE INDEX IF NOT EXISTS idx_invites_token ON invites (token);

CREATE TABLE IF NOT EXISTS matches (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    fifa_id       TEXT    NOT NULL,                 -- IdMatch (chave de upsert)
    match_number  INTEGER,                          -- 1..104
    stage         TEXT,                             -- StageName ("First Stage", "Round of 32"...)
    grp           TEXT,                             -- GroupName ("Group A") ou NULL
    utc_kickoff   TEXT    NOT NULL,                 -- Date (UTC ISO)
    status        INTEGER NOT NULL DEFAULT 1,       -- MatchStatus FIFA (1=agendado, 0=encerrado)
    home_country  TEXT,                             -- IdCountry (ex.: "MEX") p/ bandeira
    home_team     TEXT,                             -- TeamName (NULL = a definir)
    home_ph       TEXT,                             -- PlaceHolderA (ex.: "A1", "W73")
    away_country  TEXT,
    away_team     TEXT,
    away_ph       TEXT,                             -- PlaceHolderB
    home_goals    INTEGER,                          -- placar tempo normal (NULL até jogar)
    away_goals    INTEGER,
    home_pens     INTEGER,                          -- pênaltis (só decide o vencedor)
    away_pens     INTEGER,
    winner        TEXT,                             -- HOME | AWAY | DRAW
    stadium       TEXT,
    updated_at    TEXT    NOT NULL DEFAULT (datetime('now'))
);
CREATE UNIQUE INDEX IF NOT EXISTS idx_matches_fifa    ON matches (fifa_id);
CREATE INDEX        IF NOT EXISTS idx_matches_kickoff ON matches (utc_kickoff);

CREATE TABLE IF NOT EXISTS predictions (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id     INTEGER NOT NULL REFERENCES users(id)   ON DELETE CASCADE,
    match_id    INTEGER NOT NULL REFERENCES matches(id) ON DELETE CASCADE,
    pred_home   INTEGER NOT NULL,
    pred_away   INTEGER NOT NULL,
    points      INTEGER,                            -- NULL até o jogo encerrar
    is_exact    INTEGER NOT NULL DEFAULT 0,         -- desempate
    is_three    INTEGER NOT NULL DEFAULT 0,         -- desempate
    created_at  TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at  TEXT    NOT NULL DEFAULT (datetime('now'))
);
CREATE UNIQUE INDEX IF NOT EXISTS idx_pred_user_match ON predictions (user_id, match_id);
CREATE INDEX        IF NOT EXISTS idx_pred_match      ON predictions (match_id);
CREATE INDEX        IF NOT EXISTS idx_pred_user       ON predictions (user_id);

CREATE TABLE IF NOT EXISTS settings (
    key   TEXT PRIMARY KEY,
    value TEXT
);
