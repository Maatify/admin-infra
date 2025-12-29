CREATE TABLE admin_sessions (
                                id VARCHAR(64) NOT NULL,
                                admin_id VARCHAR(64) NOT NULL,

                                created_at DATETIME NOT NULL,
                                last_activity_at DATETIME NOT NULL,
                                revoked_at DATETIME NULL,

                                PRIMARY KEY (id),
                                KEY idx_admin_sessions_admin_id (admin_id),
                                KEY idx_admin_sessions_last_activity (last_activity_at),
                                KEY idx_admin_sessions_revoked (revoked_at)
);
