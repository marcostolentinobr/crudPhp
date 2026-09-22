<?php

//Configurações do banco de dados
define('MYSQL_DBLIB', 'mysql');
define('MYSQL_HOST', '127.0.0.1');
define('MYSQL_DBNAME', 'CRUD');
define('MYSQL_USERNAME', 'root');
define('MYSQL_PASSWORD', '');
define('MYSQL_CHARSET', 'utf8mb4');

//PRINT_R PRE
function pr($dado, $print_r = true)
{
    echo '<pre>';
    if ($print_r) {
        print_r($dado);
    } else {
        var_dump($dado);
    }
}

$ok = false;
$pessoaArray = [];

try {
    //CONEXAO
    $dsn = MYSQL_DBLIB . ':host=' . MYSQL_HOST . ';dbname=' . MYSQL_DBNAME . ';charset=' . MYSQL_CHARSET;
    $PDO = new PDO($dsn, MYSQL_USERNAME, MYSQL_PASSWORD, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    $mensagemErro = '';
    $acaoDescricaoOk = '';
    $postAcao = filter_input(INPUT_POST, 'ACAO', FILTER_UNSAFE_RAW);
    $postNome = filter_input(INPUT_POST, 'NOME', FILTER_SANITIZE_SPECIAL_CHARS);
    $postId   = filter_input(INPUT_POST, 'ID_PESSOA', FILTER_VALIDATE_INT);

    // INCLUIR
    if ($postAcao === 'Incluir') {
        if ($postNome) {
            $acaoDescricaoOk = 'Incluído';
            $pesIncluir = $PDO->prepare('INSERT INTO PESSOA (NOME) VALUES (:NOME)');
            $ok = $pesIncluir->execute([':NOME' => $postNome]);
            header('Location: index.php?sucesso=Incluir');
            exit;
        } else {
            $mensagemErro = 'Nome inválido.';
        }
    }
    // ALTERAR
    elseif ($postAcao === 'Alterar') {
        if ($postId && $postNome) {
            $acaoDescricaoOk = 'Alterado';
            $pesAlterar = $PDO->prepare('UPDATE PESSOA SET NOME = :NOME WHERE ID_PESSOA = :ID_PESSOA');
            $ok = $pesAlterar->execute([
                ':NOME' => $postNome,
                ':ID_PESSOA' => $postId
            ]);
            header('Location: index.php?sucesso=Alterar');
            exit;
        }
    }
    // EXCLUIR
    elseif ($postAcao === 'Excluir') {
        if ($postId) {
            $pesExcluir = $PDO->prepare('DELETE FROM PESSOA WHERE ID_PESSOA = :ID_PESSOA');
            $ok = $pesExcluir->execute([':ID_PESSOA' => $postId]);
            if ($ok && $pesExcluir->rowCount() > 0) {
                $acaoDescricaoOk = 'Excluído';
                header('Location: index.php?sucesso=Excluir');
                exit;
            }
            $ok = false;
            $mensagemErro = 'Registro não encontrado para exclusão.';
        } else {
            $mensagemErro = 'ID inválido para exclusão.';
        }
    }
    // CANCELAR
    elseif ($postAcao === 'Cancelar') {
        header('Location: index.php');
        exit;
    }
} catch (Exception $ex) {
    error_log($ex->getMessage());
    $mensagemErro = '<br><small>Ocorreu um erro interno no banco de dados.</small><br>';
}

// SUCESSO
$getSucesso = filter_input(INPUT_GET, 'sucesso', FILTER_UNSAFE_RAW);
if ($getSucesso) {
    $ok = true;
    $postAcao = $getSucesso;
    $acaoDescricaoOk = ($getSucesso === 'Incluir') ? 'Incluído' : (($getSucesso === 'Alterar') ? 'Alterado' : 'Excluído');
}

// LISTAR
$sql = '
    SELECT ID_PESSOA, 
            NOME
        FROM PESSOA
    ORDER BY NOME
';
$pessoaQuery = $PDO->query($sql);
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRUD PHP/Mysql</title>
</head>

<body>
    <div style="width: 100%; max-width: 900px; margin: 0 auto; font-family: sans-serif; padding-top: 2rem;">
        <?php
        if ($postAcao && $ok) {
            echo "<h3 style='color: green;'>$acaoDescricaoOk com sucesso! $mensagemErro</h3>";
        } elseif ($postAcao && !$ok && $mensagemErro) {
            echo "<h3 style='color: red;'>Não foi possível executar a ação! $mensagemErro</h3>";
        }
        ?>
        <table border="1" style="width: 100%; min-width: 500px; border-collapse: collapse;">
            <tr style="vertical-align: top;">

                <!-- LISTAR PESSOAS -->
                <td style="text-align: right; padding: 1rem; width: 50%;">
                    <h2 style="text-align: center;">Listar Pessoas</h2>

                    <?php
                    if ($pessoaQuery->rowCount() === 0) {
                        echo '<h5 style="text-align: center; color: blue;">Não existem pessoas para listar!</h5>';
                    }

                    while ($pessoaFetch = $pessoaQuery->fetch(PDO::FETCH_ASSOC)) {
                        $pessoaArray[$pessoaFetch['ID_PESSOA']] = $pessoaFetch;
                        echo htmlspecialchars($pessoaFetch['NOME']);
                    ?>
                        <!-- AÇÕES -->
                        <form method="POST" style="display: inline; margin-left: 0.5rem;">
                            <input type="hidden" name="ID_PESSOA" value="<?php echo (int)$pessoaFetch['ID_PESSOA'] ?>">
                            <input name="ACAO" value="Editar" type="submit">
                            <input name="ACAO" value="Excluir" type="submit">
                        </form>
                        <hr style="border: 0; border-top: 1px solid #ccc; margin: 0.5rem 0;">
                    <?php
                    }

                    // MANUTENÇÃO
                    $getEditarId = filter_input(INPUT_POST, 'ID_PESSOA', FILTER_VALIDATE_INT);
                    $pessoaAlterar = ($postAcao === 'Editar' && $getEditarId) ? @$pessoaArray[$getEditarId] : null;
                    $acaoDescricao = ($pessoaAlterar ? 'Alterar' : 'Incluir');
                    ?>
                </td>

                <!-- FORMULÁRIO -->
                <td style="text-align: center; padding: 1rem; width: 50%;">
                    <h2><?php echo $acaoDescricao ?> Pessoa</h2>
                    <form method="POST">
                        <input type="hidden" name="ID_PESSOA" value="<?php echo $pessoaAlterar ? (int)$pessoaAlterar['ID_PESSOA'] : '' ?>">

                        <label for="nome_input">Nome: </label>
                        <input id="nome_input" name="NOME" value="<?php echo $pessoaAlterar ? htmlspecialchars($pessoaAlterar['NOME']) : '' ?>" maxlength="100" required>
                        <br><br>

                        <button type="submit" name="ACAO" value="<?php echo $acaoDescricao ?>"><?php echo $acaoDescricao ?></button>
                        <button type="submit" name="ACAO" value="Cancelar">Cancelar</button>
                    </form>
                </td>
            </tr>
        </table>
    </div>
</body>

</html>