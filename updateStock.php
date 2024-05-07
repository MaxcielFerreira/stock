<?php

// Conexão com o banco de dados
$local = 'mysql:host=localhost;dbname=testemax';
$username = 'maxciel';
$password = 'EtBQqlXAmOokxF3s';
$options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];

try {
    $db = new PDO($local, $username, $password, $options);
} catch (PDOException $e) {
    die("Erro ao conectar: " . $e->getMessage());
}

// Verifica e cria a tabela se não existir
$query = "CREATE TABLE IF NOT EXISTS estoque (
    id INT UNSIGNED auto_increment NOT NULL,
    produto varchar(100) NOT NULL,
    cor varchar(100) NOT NULL,
    tamanho varchar(100) NOT NULL,
    deposito varchar(100) NOT NULL,
    data_disponibilidade DATE NOT NULL,
    quantidade INT UNSIGNED NOT NULL,
    CONSTRAINT estoque_pk PRIMARY KEY (id),
    CONSTRAINT estoque_un UNIQUE KEY (produto,cor,tamanho,deposito,data_disponibilidade)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci";
$db->exec($query);

// Verifica se o JSON foi enviado
if (isset($_POST['jsonInput'])) {
    $json = $_POST['jsonInput'];

    $produtos = json_decode($json, true);

    // Prepara a consulta SQL para inserir/atualizar cada produto
    $insert_db = $db->prepare("
        INSERT INTO estoque (produto, cor, tamanho, deposito, data_disponibilidade, quantidade)
        VALUES (:produto, :cor, :tamanho, :deposito, :data_disponibilidade, :quantidade)
    ");
    $update_db = $db->prepare("
        UPDATE estoque
        SET quantidade = quantidade + :quantidade
        WHERE produto = :produto
        AND cor = :cor
        AND tamanho = :tamanho
        AND deposito = :deposito
        AND data_disponibilidade = :data_disponibilidade
    ");

    // Verifica se o produto já existe na tabela e atualiza a quantidade se necessário
    foreach ($produtos as $produto) {

        $check_db = $db->prepare("
                SELECT COUNT(*)
                FROM estoque
                WHERE produto = :produto
                AND cor = :cor
                AND tamanho = :tamanho
                AND deposito = :deposito
                AND data_disponibilidade = :data_disponibilidade
            ");

        $check_db->execute([
            ':produto' => $produto['produto'],
            ':cor' => $produto['cor'],
            ':tamanho' => $produto['tamanho'],
            ':deposito' => $produto['deposito'],
            ':data_disponibilidade' => $produto['data_disponibilidade']
        ]);
        $exists = $check_db->fetchColumn();

        if ($exists) {
            $update_db->execute([
                ':produto' => $produto['produto'],
                ':cor' => $produto['cor'],
                ':tamanho' => $produto['tamanho'],
                ':deposito' => $produto['deposito'],
                ':data_disponibilidade' => $produto['data_disponibilidade'],
                ':quantidade' => $produto['quantidade']
            ]);
        } else {
            $insert_db->execute([
                ':produto' => $produto['produto'],
                ':cor' => $produto['cor'],
                ':tamanho' => $produto['tamanho'],
                ':deposito' => $produto['deposito'],
                ':data_disponibilidade' => $produto['data_disponibilidade'],
                ':quantidade' => $produto['quantidade']
            ]);
        }
    }
    echo "Estoque atualizado com sucesso!";
} else {
    echo "Nenhum JSON enviado.";
}