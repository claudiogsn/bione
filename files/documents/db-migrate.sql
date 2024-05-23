CREATE TABLE evento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    cliente_id INT NOT NULL,
    capacidade INT,
    data_inicio DATETIME,
    data_fim DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    local VARCHAR(255),
    cep VARCHAR(20),
    endereco VARCHAR(255),
    bairro VARCHAR(100),
    cidade VARCHAR(100),
    estado VARCHAR(50)
);

CREATE TABLE os (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evento_id INT NOT NULL,
    cliente_id INT NOT NULL,
    num_controle VARCHAR(100),
    data_montagem DATETIME,
    data_recolhimento DATETIME,
    status VARCHAR(50),
    contato_montagem VARCHAR(255),
    local_montagem VARCHAR(255),
    endereco VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE os_item (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evento_id INT NOT NULL,
    cliente_id INT NOT NULL,
    num_controle VARCHAR(100),
    material_id INT NOT NULL,
    valor DECIMAL(10, 2),
    custo DECIMAL(10, 2),
    dias_uso INT,
    data_inicial DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status VARCHAR(50)
);

CREATE TABLE os_pagamento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evento_id INT NOT NULL,
    cliente_id INT NOT NULL,
    num_controle VARCHAR(100),
    forma_pg VARCHAR(50),
    valor_pg DECIMAL(10, 2),
    data_prog DATETIME,
    data_pg DATETIME,
    status VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE cliente (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    telefone VARCHAR(50),
    email VARCHAR(100),
    cpf_cnpj VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status VARCHAR(50),
    endereco VARCHAR(255),
    bairro VARCHAR(100),
    cidade VARCHAR(100),
    estado VARCHAR(50),
    cep VARCHAR(20)
);

CREATE TABLE material (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    fabricante VARCHAR(100),
    modelo VARCHAR(100),
    categoria VARCHAR(100),
    saldo_estoque INT,
    custo_material DECIMAL(10, 2),
    valor_locacao DECIMAL(10, 2),
    status VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    patrimonio VARCHAR(50),
    sublocado BOOLEAN,
    custo_locacao DECIMAL(10, 2),
    fornecedor_id INT
);

CREATE TABLE servico (
    id INT AUTO_INCREMENT PRIMARY KEY,
    descricao VARCHAR(255) NOT NULL,
    valor_servico DECIMAL(10, 2),
    custo_servico DECIMAL(10, 2),
    terceirizado BOOLEAN,
    status VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    fornecedor_id INT
);

CREATE TABLE financeiro_conta (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doc VARCHAR(100),
    tipo VARCHAR(50),
    valor DECIMAL(10, 2),
    entidade VARCHAR(100),
    forma_pg VARCHAR(50),
    opcao_receb VARCHAR(50),
    cpf_cnpj VARCHAR(20),
    banco VARCHAR(50),
    emissao DATETIME,
    vencimento DATETIME,
    inc_ope VARCHAR(50),
    data_baixa DATETIME,
    status VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE financeiro_fornecedor (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(100),
    nome VARCHAR(255) NOT NULL,
    razao VARCHAR(255),
    endereco VARCHAR(255),
    bairro VARCHAR(100),
    cidade VARCHAR(100),
    estado VARCHAR(50),
    cep VARCHAR(20),
    cpf_cnpj VARCHAR(20),
    insc_est VARCHAR(50),
    insc_mun VARCHAR(50),
    email VARCHAR(100),
    fone VARCHAR(50),
    status VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE opcoes_recebimento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(100),
    tipo VARCHAR(50),
    nome VARCHAR(255),
    descricao TEXT,
    prazo INT,
    taxa DECIMAL(5, 2),
    status VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE financeiro_forma (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(100),
    tipo VARCHAR(50),
    nome VARCHAR(255),
    descricao TEXT,
    status VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE log_evento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    user VARCHAR(255) NOT NULL,
    ip VARCHAR(45) NOT NULL,
    dispositivo VARCHAR(255) NOT NULL
);

CREATE TABLE log_os (
    id INT AUTO_INCREMENT PRIMARY KEY,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    user VARCHAR(255) NOT NULL,
    ip VARCHAR(45) NOT NULL,
    dispositivo VARCHAR(255) NOT NULL
);

CREATE TABLE log_conta (
    id INT AUTO_INCREMENT PRIMARY KEY,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    user VARCHAR(255) NOT NULL,
    ip VARCHAR(45) NOT NULL,
    dispositivo VARCHAR(255) NOT NULL
);

