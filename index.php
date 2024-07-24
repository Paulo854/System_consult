<?php
error_reporting(E_ALL); 
ini_set('display_errors', 1);

// Conectar-se ao banco de dados com HTTPS
$servername = "Servidor MySQL";
$username = "Nome user Banco de dados";
$password = "Senha Banco de dados";
$dbname = "nome do banco de dados";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

$resultado = "";
$certificado = null; // Defina a variável $certificado como nula para evitar problemas

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $codigo = $_POST["codigo"] ?? '';
    $senha = $_POST["senha"] ?? '';

    if (!empty($codigo)) {
        $stmt = $conn->prepare("SELECT * FROM Sua_tabela WHERE identificador = ?");
        if (!$stmt) {
            die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
        }

        $stmt->bind_param("s", $codigo);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $certificado = $result->fetch_assoc();

            // Verificar a senha
            if (!empty($senha)) {
                // Compare a senha fornecida com a senha armazenada no banco de dados
                if (trim($senha) === trim($certificado['Coluna que vai comparar a senha'])) {
                    // Senha correta, continue com o processamento
                    $data_emissao = new DateTime($certificado['data_emisao']);
                    $data_emissao_formatada = $data_emissao->format('d/m/Y');

                    $nomeBorrado = $certificado['nome_certificado'];
                    $resultado = "
                    <div class='popup'>
                        <h2>Certificado válido <span style='color: green;'>&#10004;</span></h2>
                        <p><strong>Emitido para:</strong> <span id='nomeBorrado'>$nomeBorrado</span></p>
                        <p><strong>Emitido dia:</strong> $data_emissao_formatada</p>
                        <p><strong>Pela empresa:</strong> {$certificado['nome_emisor']}</p>
                        <p><strong>Nome do curso:</strong> {$certificado['curso_certificado']}</p>
                    </div>";
                } else {
                    $resultado = "<p class='erro'>Senha incorreta.</p>";
                }
            } else {
                // Caso não tenha sido fornecida a senha, exiba as informações básicas
                $data_emissao = new DateTime($certificado['data_emisao']);
                $data_emissao_formatada = $data_emissao->format('d/m/Y');

                $nomeBorrado = str_repeat("*", strlen($certificado['nome_certificado']));
                $resultado = "
                <div class='popup'>
                    <h2>Certificado válido <span style='color: green;'>&#10004;</span></h2>
                    <p><strong>Emitido para:</strong> <span id='nomeBorrado'>$nomeBorrado</span></p>
                    <button id='mostrarNomeBtn' onclick='mostrarNome()'>Mostrar Nome</button>
                    <div id='senhaForm' style='display: none;'>
                        <input type='password' id='senhaInput' placeholder='Digite a senha'>
                        <button onclick='verificarSenha()'>Verificar</button>
                        <p id='senhaErro' class='erro' style='display: none;'>Senha incorreta.</p>
                    </div>
                    <p><strong>Emitido dia:</strong> $data_emissao_formatada</p>
                    <p><strong>Pela empresa:</strong> {$certificado['nome_emisor']}</p>
                    <p><strong>Nome do curso:</strong> {$certificado['curso_certificado']}</p>
                </div>";
            }
        } else {
            $resultado = "<p class='erro'>Certificado não é válido ou não é reconhecido pela Vivamente.</p>";
        }

        $stmt->close();
    } else {
        $resultado = "<p class='erro'>Por favor, insira o código do certificado.</p>";
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta de Certificados</title>
    <link rel="icon" type="image/png" href="../img/icon.jpg"/>
    <link rel="stylesheet" href="styles.css">
    <script>
        function mostrarNome() {
            document.getElementById('senhaForm').style.display = 'block';
            document.getElementById('mostrarNomeBtn').style.display = 'none';
        }

        function verificarSenha() {
            const senhaInput = document.getElementById('senhaInput').value;
            const senhaErro = document.getElementById('senhaErro');

            if (senhaInput === '') {
                senhaErro.style.display = 'block';
            } else {
                senhaErro.style.display = 'none';
                // Submeter o formulário para verificar a senha
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>';

                const codigoInput = document.createElement('input');
                codigoInput.type = 'hidden';
                codigoInput.name = 'codigo';
                codigoInput.value = '<?php echo $codigo; ?>';

                const senhaHiddenInput = document.createElement('input');
                senhaHiddenInput.type = 'hidden';
                senhaHiddenInput.name = 'senha';
                senhaHiddenInput.value = senhaInput;

                form.appendChild(codigoInput);
                form.appendChild(senhaHiddenInput);

                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</head>
<body>
    <div class="container">
    <a href='https://www.vivamenteofi.rf.gd/termos/politica_certificado.html'>Nossa Politica</a><br>
        <h1>Consulta de Certificados</h1>
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" autocomplete="off">
            <input type="text" name="codigo" placeholder="Digite o código do certificado" required>
            <button type="submit">Consulta Simples</button>
            <input type="password" name="senha" placeholder="Digite a senha para consulta avançada">
            <button type="submit">Consulta Avançada</button>
        </form>
        <div class="resultado">
            <?php echo $resultado; ?>
        </div>
    </div>
</body>
</html>
