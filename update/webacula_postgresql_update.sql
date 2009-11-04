
-- Job descriptions
CREATE TABLE wbJobDesc (
    desc_id  SERIAL,
    name_job    CHAR(64) NOT NULL,
    retention_period CHAR(32),
    description     TEXT NOT NULL,
    PRIMARY KEY(desc_id)
);

