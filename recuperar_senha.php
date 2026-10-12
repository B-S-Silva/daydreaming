<?php
session_start();
require_once 'config.php';

// Verificar se o formulário foi enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obter o e-mail do formulário
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    
    // Validação básica
    if (empty($email)) {
        $erro = "Por favor, informe seu e-mail.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = "Por favor, insira um e-mail válido.";
    } else {
        try {
            // Conectar ao banco de dados
            $conn = conectarBD();
            
            // Verificar se o e-mail existe
            $stmt = $conn->prepare("SELECT id, nome FROM usuarios WHERE email = :email AND ativo = TRUE");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Gerar um token único para redefinição de senha
                $token = bin2hex(random_bytes(32));
                $expira = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token válido por 1 hora
                
                // Em um sistema real, você armazenaria este token em uma tabela no banco de dados
                // e enviaria um e-mail com um link para redefinir a senha
                // Aqui, apenas simulamos o processo
                
                $mensagem = "Um link para redefinição de senha foi enviado para o seu e-mail. ";
                $mensagem .= "Por favor, verifique sua caixa de entrada e siga as instruções.";
                
                // Em um sistema real, você enviaria um e-mail com o link de redefinição
                
                $sucesso = true;
            } else {
                $erro = "Não encontramos uma conta com este e-mail.";
            }
        } catch(PDOException $e) {
            $erro = "Erro ao processar a solicitação. Por favor, tente novamente mais tarde.";
            error_log("Erro na recuperação de senha: " . $e->getMessage());
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Recuperar Senha</title>
    <script src="public/js/main.js?v=1"></script>
    <style>
        .message {
            background-color: rgba(46, 204, 113, 0.2);
            border-left: 4px solid #2ecc71;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
            color: #fff;
        }
        
        .error-message {
            background-color: rgba(231, 76, 60, 0.2);
            border-left: 4px solid #e74c3c;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
            color: #fff;
        }
        
        .back-link {
            display: block;
            text-align: center;
            margin-top: 15px;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <?php include 'header_status.php'; ?>
    
    <section class="container">
        <div class="login-container">
            <div class="circle circle-one"></div>
            <div class="form-container">
                <h1 class="opacity">RECUPERAR SENHA</h1>
                
                <?php if (isset($sucesso) && $sucesso): ?>
                <div class="message">
                    <?php echo $mensagem; ?>
                </div>
                <a href="login.php" class="back-link opacity">VOLTAR PARA LOGIN</a>
                <?php else: ?>
                
                <?php if (isset($erro)): ?>
                <div class="error-message">
                    <?php echo $erro; ?>
                </div>
                <?php endif; ?>
                
                <p>Informe seu e-mail para receber instruções de recuperação de senha.</p>
                
                <form method="POST" action="">
                    <input type="email" name="email" placeholder="E-MAIL" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" />
                    <button type="submit" class="opacity">ENVIAR</button>
                </form>
                <a href="login.php" class="back-link opacity">VOLTAR PARA LOGIN</a>
                <?php endif; ?>
            </div>
            <div class="circle circle-two"></div>
        </div>
        <div class="theme-btn-container"></div>
    </section>
</body>
</html>