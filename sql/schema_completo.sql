CREATE TABLE IF NOT EXISTS trabajadores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provider_id VARCHAR(100) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    edad TINYINT UNSIGNED NOT NULL,
    celular VARCHAR(30) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    instagram VARCHAR(100) NULL,
    estudios ENUM('primario', 'secundario', 'terciario', 'universitario') NOT NULL,
    foto VARCHAR(255) NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS oficios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provider_id VARCHAR(100) NOT NULL,
    rubro VARCHAR(100) NOT NULL,
    oficio VARCHAR(150) NOT NULL,
    certificaciones TEXT NULL,
    descripcion TEXT NULL,
    foto_trabajo VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_provider_oficio (provider_id),
    CONSTRAINT fk_oficio_trabajador FOREIGN KEY (provider_id)
        REFERENCES trabajadores(provider_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS oficios_media (
    id INT AUTO_INCREMENT PRIMARY KEY,
    oficio_id INT NOT NULL,
    tipo ENUM('foto', 'video') NOT NULL,
    archivo VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_oficio (oficio_id),
    CONSTRAINT fk_media_oficio FOREIGN KEY (oficio_id)
        REFERENCES oficios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS ratings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provider_id VARCHAR(100) NOT NULL,
    stars TINYINT UNSIGNED NOT NULL CHECK (stars BETWEEN 1 AND 5),
    ip_address VARCHAR(45) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_provider (provider_id),
    UNIQUE KEY unique_ip_provider (provider_id, ip_address)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('trabajador', 'usuario') NOT NULL,
    identificador VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expira DATETIME NOT NULL,
    usado TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS busquedas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    termino VARCHAR(150) NOT NULL,
    usuario_nombre VARCHAR(200) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_termino (termino)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS vistas_perfil (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provider_id VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_provider (provider_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS contactos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provider_id VARCHAR(100) NOT NULL,
    tipo ENUM('llamada', 'whatsapp', 'email', 'instagram') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_provider (provider_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;