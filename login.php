<?php
session_start();
require_once 'config.php';

// Verificar se o formulário foi enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obter os dados do formulário
    $usuario = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
    $senha = isset($_POST['senha']) ? trim($_POST['senha']) : '';
    
    // Validação básica
    if (empty($usuario) || empty($senha)) {
        $erro = "Por favor, preencha todos os campos.";
    } else {
        try {
            // Conectar ao banco de dados
            $conn = conectarBD();
            
            // Primeiro, verificar se o usuário existe (independente do status)
            $stmt = $conn->prepare("SELECT id, usuario, senha, nome, is_admin, ativo FROM usuarios WHERE usuario = :usuario");
            $stmt->bindParam(':usuario', $usuario);
            $stmt->execute();
            
            // Verificar se o usuário existe
            if ($stmt->rowCount() > 0) {
                $usuario_dados = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Verificar a senha primeiro
                if (password_verify($senha, $usuario_dados['senha'])) {
                    // Senha correta, agora verificar se o usuário está ativo
                    if (!$usuario_dados['ativo']) {
                        // Usuário banido
                        $erro = "Este usuário foi banido por violar as regras de conduta do Portal!";
                        
                        // Registrar tentativa de login de usuário banido
                        $ip = $_SERVER['REMOTE_ADDR'];
                        $stmt = $conn->prepare("INSERT INTO logs_acesso (usuario_id, ip, sucesso) VALUES (:usuario_id, :ip, FALSE)");
                        $stmt->bindParam(':usuario_id', $usuario_dados['id']);
                        $stmt->bindParam(':ip', $ip);
                        $stmt->execute();
                    } else {
                        // Senha correta, iniciar a sessão
                        $_SESSION['usuario_id'] = $usuario_dados['id'];
                        $_SESSION['usuario_nome'] = $usuario_dados['nome'];
                        $_SESSION['usuario_login'] = $usuario_dados['usuario'];
                        $_SESSION['is_admin'] = (bool)$usuario_dados['is_admin'];
                        
                        // Registrar o login bem-sucedido
                        $ip = $_SERVER['REMOTE_ADDR'];
                        $stmt = $conn->prepare("INSERT INTO logs_acesso (usuario_id, ip, sucesso) VALUES (:usuario_id, :ip, TRUE)");
                        $stmt->bindParam(':usuario_id', $usuario_dados['id']);
                        $stmt->bindParam(':ip', $ip);
                        $stmt->execute();
                        
                        // Atualizar o último acesso
                        $stmt = $conn->prepare("UPDATE usuarios SET ultimo_acesso = NOW() WHERE id = :id");
                        $stmt->bindParam(':id', $usuario_dados['id']);
                        $stmt->execute();
                        
                        // Redirecionar baseado no tipo de usuário
                        if ($usuario_dados['is_admin']) {
                            header("Location: admin_forum.php");
                        } else {
                            header("Location: index.php");
                        }
                        exit;
                    }
                } else {
                    // Senha incorreta
                    $erro = "Usuário ou senha incorretos.";
                    
                    // Registrar a tentativa de login mal-sucedida
                    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE usuario = :usuario");
                    $stmt->bindParam(':usuario', $usuario);
                    $stmt->execute();
                    $usuario_id = $stmt->fetch(PDO::FETCH_ASSOC)['id'];
                    
                    $ip = $_SERVER['REMOTE_ADDR'];
                    $stmt = $conn->prepare("INSERT INTO logs_acesso (usuario_id, ip, sucesso) VALUES (:usuario_id, :ip, FALSE)");
                    $stmt->bindParam(':usuario_id', $usuario_id);
                    $stmt->bindParam(':ip', $ip);
                    $stmt->execute();
                }
            } else {
                // Usuário não encontrado
                $erro = "Usuário ou senha incorretos.";
            }
        } catch(PDOException $e) {
            $erro = "Erro ao processar o login. Por favor, tente novamente mais tarde.";
            error_log("Erro no login: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="public/css/style.css">
    <title>Formulário de Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="public/js/main.js?v=1"></script>
    
    <style>
        body {
            background: linear-gradient(135deg, #187bcb 0%, #6c5ce7 100%) !important;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }
        
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('dream-clouds.svg') center/cover;
            opacity: 0.1;
            z-index: -1;
        }
        
        .container {
            position: relative;
            z-index: 1;
        }
    </style>
</head>
<body>
    <?php include 'header_status.php'; ?>
    
    <section class="container">
        <div class="login-container">
            <div class="circle circle-one"></div>
            <div class="form-container">
                <h1 class="opacity">LOGIN</h1>
                
                <?php if (isset($_SESSION['mensagem'])): ?>
                <div class="success-message">
                    <?php 
                    echo $_SESSION['mensagem']; 
                    unset($_SESSION['mensagem']); // Limpar a mensagem após exibir
                    ?>
                </div>
                <?php endif; ?>
                
                <?php if (isset($erro)): ?>
                <div class="error-message">
                    <?php echo $erro; ?>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <input type="text" name="usuario" placeholder="USUÁRIO" value="<?php echo isset($_POST['usuario']) ? htmlspecialchars($_POST['usuario']) : ''; ?>" />
                    <input type="password" name="senha" placeholder="SENHA" />
                    <button type="submit" class="opacity">ENTRAR</button>
                </form>
                <div class="register-forget opacity">
                    <a href="cadastro.php">CADASTRAR</a>
                    <a href="recuperar_senha.php">ESQUECI A SENHA</a>
                </div>
            </div>
            <div class="circle circle-two"></div>
        </div>
        <div class="theme-btn-container"></div>
    </section>
</body>
</html>