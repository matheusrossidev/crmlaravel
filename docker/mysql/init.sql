-- Garante que o banco existe com charset correto
CREATE DATABASE IF NOT EXISTS `plataforma360`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

-- Garante privilégios completos para o usuário da aplicação
GRANT ALL PRIVILEGES ON `plataforma360`.* TO 'crm'@'%';
FLUSH PRIVILEGES;
