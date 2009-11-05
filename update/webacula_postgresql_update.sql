
-- Job descriptions
CREATE TABLE wbJobDesc (
    desc_id  SERIAL,
    name_job    CHAR(64) UNIQUE NOT NULL,
    retention_period CHAR(32),
    description     TEXT NOT NULL,
    PRIMARY KEY(desc_id)
);
CREATE INDEX wbidx2 ON wbJobDesc (name_job);
