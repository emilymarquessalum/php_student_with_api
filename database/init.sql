DROP TABLE IF EXISTS presenca CASCADE;
DROP TABLE IF EXISTS dia_de_aula CASCADE;
DROP TABLE IF EXISTS aluno CASCADE;
DROP TABLE IF EXISTS professor CASCADE;
DROP TABLE IF EXISTS integrante_da_turma CASCADE;
DROP TABLE IF EXISTS turma CASCADE;
DROP TABLE IF EXISTS disciplina CASCADE;
DROP TABLE IF EXISTS usuario CASCADE;

CREATE TABLE IF NOT EXISTS usuario (
    email VARCHAR(255) PRIMARY KEY,
    senha VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS disciplina (
    id VARCHAR(255) PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS turma (
    id VARCHAR(255) PRIMARY KEY,
    nome_turma VARCHAR(255) NOT NULL,
    disciplina_id VARCHAR(255) NOT NULL,
    year TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (disciplina_id) REFERENCES disciplina(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS professor (
    id VARCHAR(255) PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (email) REFERENCES usuario(email) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS aluno (
    id VARCHAR(255) PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    matricula VARCHAR(255) NOT NULL UNIQUE,
    name VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (email) REFERENCES usuario(email) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS integrante_da_turma (
    id SERIAL PRIMARY KEY,  
    turma_id VARCHAR(255) NOT NULL,
    professor_id VARCHAR(255) NULL,  
    aluno_id VARCHAR(255) NULL,     
    tipo VARCHAR(20) NOT NULL CHECK (tipo IN ('professor', 'aluno')),
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (turma_id) REFERENCES turma(id) ON DELETE CASCADE,
    FOREIGN KEY (professor_id) REFERENCES professor(id) ON DELETE CASCADE,
    FOREIGN KEY (aluno_id) REFERENCES aluno(id) ON DELETE CASCADE,
     
    CHECK (
        (tipo = 'professor' AND professor_id IS NOT NULL AND aluno_id IS NULL) OR
        (tipo = 'aluno' AND aluno_id IS NOT NULL AND professor_id IS NULL)
    ),
      
    UNIQUE(turma_id, professor_id),
    UNIQUE(turma_id, aluno_id)
);

CREATE TABLE IF NOT EXISTS dia_de_aula (
    id VARCHAR(255) PRIMARY KEY,
    turma_id VARCHAR(255) NOT NULL,
    data TIMESTAMP NOT NULL,
    aula_foi_dada BOOLEAN NOT NULL DEFAULT FALSE,
    professor_id VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (turma_id) REFERENCES turma(id) ON DELETE CASCADE,
    FOREIGN KEY (professor_id) REFERENCES professor(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS presenca (
    id VARCHAR(255) PRIMARY KEY,
    aluno_id VARCHAR(255) NOT NULL,
    dia_aula_id VARCHAR(255) NOT NULL,
    timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (aluno_id) REFERENCES aluno(id) ON DELETE CASCADE,
    FOREIGN KEY (dia_aula_id) REFERENCES dia_de_aula(id) ON DELETE CASCADE,
      
    UNIQUE(aluno_id, dia_aula_id)
);

INSERT INTO disciplina (id, name, description) VALUES 
    ('disc-001', 'Matemática', 'Matemática básica e avançada'),
    ('disc-002', 'Física', 'Física fundamental e aplicada'),
    ('disc-003', 'Programação', 'Desenvolvimento de software e algoritmos'),
    ('disc-004', 'História', 'História mundial e do Brasil'),
    ('disc-005', 'Português', 'Língua portuguesa e literatura')
ON CONFLICT (id) DO NOTHING;

INSERT INTO usuario (email, senha) VALUES 
    ('admin@escola.com', '123456'),
    ('professor@escola.com', '123456'),
    ('prof.maria@escola.com', '123456'),
    ('prof.carlos@escola.com', '123456'),
    ('aluno1@escola.com', '123456'),
    ('aluno2@escola.com', '123456'),
    ('aluno3@escola.com', '123456'),
    ('aluno4@escola.com', '123456')
ON CONFLICT (email) DO NOTHING;

INSERT INTO professor (id, email, name) VALUES 
    ('prof-001', 'professor@escola.com', 'Professor João Silva'),
    ('prof-002', 'prof.maria@escola.com', 'Professora Maria Santos'),
    ('prof-003', 'prof.carlos@escola.com', 'Professor Carlos Oliveira')
ON CONFLICT (id) DO NOTHING;

INSERT INTO aluno (id, email, matricula, name) VALUES 
    ('aluno-001', 'aluno1@escola.com', '2024001', 'Ana Costa'),
    ('aluno-002', 'aluno2@escola.com', '2024002', 'Bruno Lima'),
    ('aluno-003', 'aluno3@escola.com', '2024003', 'Carla Mendes'),
    ('aluno-004', 'aluno4@escola.com', '2024004', 'Daniel Rocha')
ON CONFLICT (id) DO NOTHING;

INSERT INTO turma (id, nome_turma, disciplina_id, year) VALUES 
    ('turma-001', '3º Ano A - Matemática', 'disc-001', '2024-01-01 00:00:00'),
    ('turma-002', '2º Ano B - Física', 'disc-002', '2024-01-01 00:00:00'),
    ('turma-003', '1º Ano C - Programação', 'disc-003', '2024-01-01 00:00:00')
ON CONFLICT (id) DO NOTHING;

INSERT INTO integrante_da_turma (turma_id, professor_id, tipo) VALUES 
    ('turma-001', 'prof-001', 'professor'),
    ('turma-003', 'prof-001', 'professor')
ON CONFLICT (turma_id, professor_id) DO NOTHING;

INSERT INTO integrante_da_turma (turma_id, professor_id, tipo) VALUES 
    ('turma-002', 'prof-002', 'professor')
ON CONFLICT (turma_id, professor_id) DO NOTHING;

INSERT INTO integrante_da_turma (turma_id, aluno_id, tipo) VALUES 
    ('turma-001', 'aluno-001', 'aluno'),
    ('turma-001', 'aluno-002', 'aluno')
ON CONFLICT (turma_id, aluno_id) DO NOTHING;

INSERT INTO integrante_da_turma (turma_id, aluno_id, tipo) VALUES 
    ('turma-002', 'aluno-002', 'aluno'),
    ('turma-002', 'aluno-003', 'aluno')
ON CONFLICT (turma_id, aluno_id) DO NOTHING;

INSERT INTO integrante_da_turma (turma_id, aluno_id, tipo) VALUES 
    ('turma-003', 'aluno-001', 'aluno'),
    ('turma-003', 'aluno-004', 'aluno')
ON CONFLICT (turma_id, aluno_id) DO NOTHING;

INSERT INTO dia_de_aula (id, turma_id, data, aula_foi_dada, professor_id) VALUES 
    ('aula-001', 'turma-001', '2024-07-24 08:00:00', true, 'prof-001'),
    ('aula-002', 'turma-001', '2024-07-25 08:00:00', false, 'prof-001'),
    ('aula-003', 'turma-002', '2024-07-24 10:00:00', true, 'prof-002'),
    ('aula-004', 'turma-003', '2024-07-26 14:00:00', false, 'prof-001')
ON CONFLICT (id) DO NOTHING;

INSERT INTO presenca (id, aluno_id, dia_aula_id) VALUES 
    ('pres-001', 'aluno-001', 'aula-001'),
    ('pres-002', 'aluno-002', 'aula-001'),
    ('pres-003', 'aluno-002', 'aula-003'),
    ('pres-004', 'aluno-003', 'aula-003')
ON CONFLICT (id) DO NOTHING;