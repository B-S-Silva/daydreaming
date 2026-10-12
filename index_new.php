<?php
session_start();

// Verificar se o usuário está logado
$usuario_logado = isset($_SESSION['logado']) && $_SESSION['logado'] === true;
$usuario_nome = $usuario_logado ? $_SESSION['usuario_nome'] : '';
$usuario_login = $usuario_logado ? $_SESSION['usuario_login'] : '';

// Conectar ao banco para estatísticas (se logado)
$estatisticas = null;
if ($usuario_logado) {
    try {
        require_once 'config.php';
        $pdo = conectarBD();
        
        // Buscar estatísticas do usuário
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_testes,
                AVG(CASE 
                    WHEN st.tipo_prova = 'sat' THEN (st.acertos / 120) * 100
                    WHEN st.tipo_prova = 'toefl' THEN (st.acertos / 100) * 100
                    WHEN st.tipo_prova = 'ielts' THEN (st.acertos / 40) * 100
                    WHEN st.tipo_prova = 'gre' THEN (st.acertos / 80) * 100
                    ELSE (st.acertos / 120) * 100
                END) as media_pontuacao
            FROM sessoes_teste st
            WHERE st.usuario_id = ? AND st.status = 'finalizado'
        ");
        $stmt->execute([$_SESSION['usuario_id']]);
        $estatisticas = $stmt->fetch();
    } catch (Exception $e) {
        // Silenciar erro de conexão
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DayDreaming - Sistema de Simulados Internacionais</title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.12.9/dist/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
    
    <style>
        /* Estilos base */
        body {
            overflow-x: hidden;
        }

        .banner-section {
            width: 100%;
            height: auto;
            margin-top: -8%;
            text-align: center;
            
        }
        
        .navbutton {
            color: white;
            font-size: clamp(10px, 2vw, 20px);
            text-align: center;
            width: 100%;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .navbutton:hover {
            background-color: rgba(255,255,255,0.1);
            transform: translateY(-2px);
        }
        
        .text1 {
            font-size: clamp(10px, 2vw, 20px);
        }
        
        .title1 {
            color: #03254c;
            font-weight: bold;
            font-size: clamp(20px, 4vw, 40px);
        }
        
        .btn {
            margin: 20px auto;
            display: block;
            font-size: clamp(12px, 1.5vw, 18px);
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        img {
            max-width: 100%;
            height: auto;
        }
        
        /* Cabeçalho responsivo */
        .header-container {
            background-color: #03254c;
            padding: 20px 0;
            margin-top: 10px; /* Espaço para o header de status */
        }
        
        .logo-container img {
            max-width: 100%;
            height: auto;
        }
        
        /* Status do usuário */
        .user-status {
            color: white;
            font-size: 14px;
            text-align: center;
        }
        
        .user-stats {
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
            padding: 10px;
            margin-top: 10px;
        }
        
        /* Espaçamento de seções */
        .section-spacing {
            
            margin-bottom: 2%;
        }
        
        /* Cards de exames */
        .exam-card {
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .exam-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }
        
        /* Media queries */
        @media (max-width: 768px) {
            .header-container {
                padding: 10px 0;
            }
            
            .section-spacing {
                margin-top: 10%;
                margin-bottom: 10%;
            }
            
            .reverse-columns-mobile {
                flex-direction: column-reverse;
            }
        }
        
        @media (max-width: 576px) {
            .btn {
                width: 80%;
            }
        }
    </style>
</head>
<body>
    <?php include 'header_status.php'; ?>

   
  

    <!-- Menu de navegação -->
    <div class="container-fluid">
        <div class="row">

            <div class="col-lg-3 col-md-3 col-sm-6 col-6 exam-card" style="background-color: #2a9df4; min-height: 80px; border: 3px solid white; padding: 10px;" onclick="<?php echo $usuario_logado ? "location.href='executar_teste.php?tipo=toefl'" : "location.href='login.php'"; ?>">
                <p class="text1" style="color: white;">Quem Somos <br>
                    <span style="display: block; text-align: right;">Inglês</span>
                </p>
            </div>

            <div class="col-lg-3 col-md-3 col-sm-6 col-6 exam-card" style="background-color: #2a9df4; min-height: 80px; border: 3px solid white; padding: 10px;" onclick="<?php echo $usuario_logado ? "location.href='executar_teste.php?tipo=toefl'" : "location.href='login.php'"; ?>">
                <p class="text1" style="color: white;">Teste Vocacional <br>
                    <span style="display: block; text-align: right;">Inglês</span>
                </p>
            </div>

            <div class="col-lg-3 col-md-3 col-sm-6 col-6 exam-card" style="background-color: #2a9df4; min-height: 80px; border: 3px solid white; padding: 10px;" onclick="<?php echo $usuario_logado ? "location.href='executar_teste.php?tipo=toefl'" : "location.href='login.php'"; ?>">
                <p class="text1" style="color: white;">Simulador Prático <br>
                    <span style="display: block; text-align: right;">Inglês</span>
                </p>
            </div>

             <div class="col-lg-3 col-md-3 col-sm-6 col-6 exam-card" style="background-color: #2a9df4; min-height: 80px; border: 3px solid white; padding: 10px;" onclick="<?php echo $usuario_logado ? "location.href='executar_teste.php?tipo=toefl'" : "location.href='login.php'"; ?>">
                <p class="text1" style="color: white;">Comunidade <br>
                    <span style="display: block; text-align: right;">Inglês</span>
                </p>
            </div>
        </div>
    </div>

    <!-- Seção banner -->
     <section class="banner-section">
        <img src="Imagens/primeira_sessão.png" alt="Primeira sessão" class="img-fluid banner_s1">
    </section>

    <!-- Seção de exames e bolsas -->
    <div class="container-fluid section-spacing">
        <div class="row text-center">
            <div class="col-md-4 col-sm-4 col-12 mb-2">
                <p class="text1" style="color: #929DE3;">Proficiência em Idiomas</p>
            </div>
            <div class="col-md-4 col-sm-4 col-12 mb-2">
                <p class="text1" style="color: #187bcd;">Exames Padronizados</p>
            </div>
            <div class="col-md-4 col-sm-4 col-12 mb-2">
                <p class="text1" style="color: #1167b1;">Principais Programa de Bolsas</p>
            </div>
        </div>
        <div class="row">
            <!-- Idiomas -->    
            <div class="col-lg-3 col-md-3 col-sm-6 col-6 exam-card" style="background-color: #929DE3; min-height: 80px; border: 3px solid white; padding: 10px;" onclick="<?php echo $usuario_logado ? "location.href='executar_teste.php?tipo=toefl'" : "location.href='login.php'"; ?>">
                <p class="text1" style="color: white;">TOEFL | IELTS <br>
                    <span style="display: block; text-align: right;">Inglês</span>
                </p>
            </div>
            
            <div class="col-lg-3 col-md-3 col-sm-6 col-6 exam-card" style="background-color: #929DE3; min-height: 80px; border: 3px solid white; padding: 10px;">
                <p class="text1" style="color: white;">DELF | DALF <br>
                    <span style="display: block; text-align: right;">Francês</span>
                </p>
            </div>
            
            <div class="col-lg-3 col-md-3 col-sm-6 col-6 exam-card" style="background-color: #929DE3; min-height: 80px; border: 3px solid white; padding: 10px;">
                <p class="text1" style="color: white;">TestDaF/DSH <br>
                    <span style="display: block; text-align: right;">Alemão</span>
                </p>
            </div>
            
            <div class="col-lg-3 col-md-3 col-sm-6 col-6 exam-card" style="background-color: #929DE3; min-height: 80px; border: 3px solid white; padding: 10px;">
                <p class="text1" style="color: white;">JLPT<br>
                    <span style="display: block; text-align: right;">Japonês</span>
                </p>
            </div>
            
            <!-- Exames -->    
            <div class="col-lg-3 col-md-3 col-sm-6 col-6 exam-card" style="background-color: #187bcd; min-height: 80px; border: 3px solid white; padding: 10px;" onclick="<?php echo $usuario_logado ? "location.href='executar_teste.php?tipo=sat'" : "location.href='login.php'"; ?>">
                <p class="text1" style="color: white;">SAT <br>
                    <span style="display: block; text-align: right;">EUA</span>
                </p>
            </div>
            
            <div class="col-lg-3 col-md-3 col-sm-6 col-6 exam-card" style="background-color: #187bcd; min-height: 80px; border: 3px solid white; padding: 10px;">
                <p class="text1" style="color: white;">ACT <br>
                    <span style="display: block; text-align: right;">EUA</span>
                </p>
            </div>
            
            <div class="col-lg-3 col-md-3 col-sm-6 col-6 exam-card" style="background-color: #187bcd; min-height: 80px; border: 3px solid white; padding: 10px;" onclick="<?php echo $usuario_logado ? "location.href='executar_teste.php?tipo=gre'" : "location.href='login.php'"; ?>">
                <p class="text1" style="color: white;">GRE <br>
                    <span style="display: block; text-align: right;">EUA</span>
                </p>
            </div>
            
            <div class="col-lg-3 col-md-3 col-sm-6 col-6 exam-card" style="background-color: #187bcd; min-height: 80px; border: 3px solid white; padding: 10px;">
                <p class="text1" style="color: white;">TesteAS <br>
                    <span style="display: block; text-align: right;">Alemanha</span>
                </p>
            </div>
            
            <!-- Bolsas -->    
            <div class="col-lg-3 col-md-3 col-sm-6 col-6 exam-card" style="background-color: #1167b1; min-height: 80px; border: 3px solid white; padding: 10px;">
                <p class="text1" style="color: white;">MEXT
                    <span style="display: block; text-align: right;">Japão</span>
                </p>
            </div>
            
            <div class="col-lg-3 col-md-3 col-sm-6 col-6 exam-card" style="background-color: #1167b1; min-height: 80px; border: 3px solid white; padding: 10px;">
                <p class="text1" style="color: white;">Fullbright
                    <span style="display: block; text-align: right;">EUA</span>
                </p>
            </div>
            
            <div class="col-lg-3 col-md-3 col-sm-6 col-6 exam-card" style="background-color: #1167b1; min-height: 80px; border: 3px solid white; padding: 10px;">
                <p class="text1" style="color: white;">CSC
                    <span style="display: block; text-align: right;">China</span>
                </p>
            </div>
            
            <div class="col-lg-3 col-md-3 col-sm-6 col-6 exam-card" style="background-color: #1167b1; min-height: 80px; border: 3px solid white; padding: 10px;">
                <p class="text1" style="color: white;">DAAD
                    <span style="display: block; text-align: right;">Alemanha</span>
                </p>
            </div>
        </div>
    </div>

    <!-- Seção Teste Vocacional -->
    <div id="teste-vocacional" class="container-fluid section-spacing">
        <div class="row">
            <div class="col-lg-1 col-md-1 d-none d-md-block"></div>
            <div class="col-lg-5 col-md-5 col-sm-12 col-12 mb-4">
                <p class="title1">Descubra sua vocação</p>
                <p class="text1">De medicina à engenharia, de artes à diplomacia — o mundo te espera. Descubra qual caminho internacional combina com você.</p>
                <button type="button" class="btn btn-primary btn-lg" onclick="alert('Funcionalidade em desenvolvimento!')">Faça já</button>
            </div>
            <div class="col-lg-1 col-md-1 d-none d-md-block"></div>
            <div class="col-lg-5 col-md-5 col-sm-12 col-12">
                <img src="Imagens/segunda sessão.png" alt="Segunda sessão" class="img-fluid">
            </div>
        </div>
    </div>

    <!-- Seção Bolsas de Estudo -->
    <div class="container-fluid section-spacing">
        <div class="row reverse-columns-mobile">
            <div class="col-lg-1 col-md-1 d-none d-md-block"></div>
            <div class="col-lg-5 col-md-5 col-sm-12 col-12 mb-4">
                <img src="Imagens/image 52.png" alt="Bolsas de estudo" class="img-fluid">
            </div>
            <div class="col-lg-1 col-md-1 d-none d-md-block"></div>
            <div class="col-lg-5 col-md-5 col-sm-12 col-12">
                <p class="text1">Navegue por países com programas de bolsas de estudo internacionais e conheça, de forma prática e visual, as opções disponíveis para você.
                Cada país conta com uma página exclusiva que reúne informações essenciais, como tipos de bolsas oferecidas, requisitos, universidades parceiras, dicas culturais e dados sobre o sistema educacional local.</p>
                <button type="button" class="btn btn-primary btn-lg" onclick="alert('Funcionalidade em desenvolvimento!')">Explore</button>
            </div>
        </div>
    </div>

    <!-- Seção Ranking de Universidades -->
    <div class="container-fluid section-spacing">
        <div class="row">
            <div class="col-lg-1 col-md-1 d-none d-md-block"></div>
            <div class="col-lg-5 col-md-5 col-sm-12 col-12 mb-4">
                <p class="title1">Ranking de universidades</p>
                <p class="text1">Veja a melhor para o seu perfil</p>
                <button type="button" class="btn btn-primary btn-lg" onclick="alert('Funcionalidade em desenvolvimento!')">Checar rank</button>
            </div>
            <div class="col-lg-5 col-md-5 col-sm-12 col-12">
                <img src="imagens/image36.png" alt="Ranking de universidades" class="img-fluid">
            </div>
            <div class="col-lg-1 col-md-1 d-none d-md-block"></div>
        </div>
    </div>

    <!-- Seção Simuladores e Ferramentas -->
    <div class="container-fluid section-spacing">
        <div class="row">
            <div class="col-lg-1 col-md-1 d-none d-md-block"></div>
            <div class="col-lg-4 col-md-5 col-sm-12 col-12 mb-5">
                <p class="title1">Simulador de Prova</p>
                <p class="text1">Faça simulados para provas de diversos países</p>
                <img src="Imagens/image 37 (1).png" alt="Simulador de Prova" class="img-fluid mb-3">
                <button type="button" class="btn btn-primary btn-lg" onclick="location.href='<?php echo $usuario_logado ? 'simulador_provas.php' : 'login.php'; ?>'">
                    <?php echo $usuario_logado ? 'Realizar testes' : 'Entrar para testar'; ?>
                </button>
            </div>
            <div class="col-lg-2 col-md-1 d-none d-md-block"></div>
            <div class="col-lg-4 col-md-5 col-sm-12 col-12 mb-5">
                <p class="title1">Conversor de GPA</p>
                <p class="text1">Insira suas notas do ensino médio e calcule seu GPA</p>
                <img src="Imagens/Rectangle 73.png" alt="Conversor de GPA" class="img-fluid mb-3">
                <button type="button" class="btn btn-primary btn-lg" onclick="alert('Funcionalidade em desenvolvimento!')">Converta suas notas</button>
            </div>
            <div class="col-lg-1 col-md-1 d-none d-md-block"></div>
        </div>
        <div class="row section-spacing">
            <div class="col-lg-1 col-md-1 d-none d-md-block"></div>
            <div class="col-lg-4 col-md-5 col-sm-12 col-12 mb-5">
                <p class="title1">Simulador de Entrevista</p>
                <p class="text1">Simule como seria uma entrevista para universidades dos seus sonhos</p>
                <img src="Imagens/image 38.png" alt="Simulador de Entrevista" class="img-fluid mb-3">
                <button type="button" class="btn btn-primary btn-lg" onclick="alert('Funcionalidade em desenvolvimento!')">Realizar entrevista</button>
            </div>
            <div class="col-lg-2 col-md-1 d-none d-md-block"></div>
            <div class="col-lg-4 col-md-5 col-sm-12 col-12 mb-5">
                <p class="title1">Descoberta Final</p>
                <p class="text1">Com bases nas notas, entrevista e provas, veja se foi aprovado</p>
                <img src="Imagens/image 39.png" alt="Descoberta Final" class="img-fluid mb-3">
                <button type="button" class="btn btn-primary btn-lg" onclick="alert('Funcionalidade em desenvolvimento!')">Ver resultado</button>
            </div>
            <div class="col-lg-1 col-md-1 d-none d-md-block"></div>
        </div>
    </div>

    <!-- Seção Fórum Comunitário -->
    <div id="comunidade" class="container-fluid section-spacing">
        <div class="row reverse-columns-mobile">
            <div class="col-lg-1 col-md-1 d-none d-md-block"></div>
            <div class="col-lg-5 col-md-5 col-sm-12 col-12 mb-4">
                <img src="Imagens/image 51.png" alt="Fórum Comunitário" class="img-fluid">
            </div>
            <div class="col-lg-1 col-md-1 d-none d-md-block"></div>
            <div class="col-lg-5 col-md-5 col-sm-12 col-12">
                <p class="text1">Pensado como um espaço de colaboração, o Fórum Comunitário da nossa plataforma foi criado para promover a troca de informações, experiências e dúvidas entre estudantes que estão em busca de bolsas de estudo no exterior. Aqui, cada usuário pode abrir tópicos, responder perguntas, compartilhar oportunidades e se conectar com outros que estão trilhando caminhos semelhantes.</p>
                <button type="button" class="btn btn-primary btn-lg" onclick="alert('Funcionalidade em desenvolvimento!')">Cheque o Fórum</button>
            </div>
        </div>
    </div>

    <!-- Seção Quem Somos -->
    <div id="quem-somos" class="container-fluid section-spacing" style="background-color: #f8f9fa; padding: 50px 0;">
        <div class="row">
            <div class="col-lg-2 col-md-1 d-none d-md-block"></div>
            <div class="col-lg-8 col-md-10 col-sm-12 col-12 text-center">
                <p class="title1">Quem Somos</p>
                <p class="text1">O DayDreaming é uma plataforma completa para estudantes que sonham em estudar no exterior. Oferecemos simulados de exames internacionais, informações sobre bolsas de estudo, rankings de universidades e uma comunidade ativa para troca de experiências.</p>

                <?php if ($usuario_logado): ?>
                    <div class="mt-4 p-4" style="background: rgba(3, 37, 76, 0.1); border-radius: 15px;">
                        <h5 style="color: #03254c;">Bem-vindo de volta, <?php echo htmlspecialchars($usuario_nome); ?>! 🎓</h5>
                        <p class="text1">Continue sua jornada de preparação para estudar no exterior.</p>
                        <div class="row mt-3">
                            <div class="col-md-4">
                                <a href="simulador_provas.php" class="btn btn-primary btn-block">Fazer Simulados</a>
                            </div>
                            <div class="col-md-4">
                                <a href="historico_provas.php" class="btn btn-outline-primary btn-block">Ver Histórico</a>
                            </div>
                            <div class="col-md-4">
                                <a href="logout.php" class="btn btn-outline-secondary btn-block">Sair</a>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="mt-4">
                        <p class="text1"><strong>Faça login para acessar todas as funcionalidades!</strong></p>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <a href="login.php" class="btn btn-primary btn-lg btn-block">Entrar</a>
                            </div>
                            <div class="col-md-6">
                                <a href="registro.php" class="btn btn-outline-primary btn-lg btn-block">Criar Conta</a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-lg-2 col-md-1 d-none d-md-block"></div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="container-fluid section-spacing text-center" style="background-color: #03254c; color: white; padding: 40px 0;">
        <img src="Imagens/Logo_DayDreaming_trasp 1.png" alt="Logo DayDreaming" class="img-fluid" style="max-width: 200px;">
        <p class="text1 mt-3">© 2024 DayDreaming - Sistema de Simulados Internacionais</p>
        <p class="text1">Todos os direitos reservados</p>

        <?php if ($usuario_logado): ?>
            <div class="mt-3">
                <small>Logado como: <?php echo htmlspecialchars($usuario_nome); ?> | <a href="logout.php" style="color: #2a9df4;">Sair</a></small>
            </div>
        <?php endif; ?>
    </footer>

    <script>
        // Função para scroll suave para seções
        function scrollToSection(sectionId) {
            const element = document.getElementById(sectionId);
            if (element) {
                element.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        }

        // Adicionar efeitos de hover nos cards de exames
        document.addEventListener('DOMContentLoaded', function() {
            const examCards = document.querySelectorAll('.exam-card');
            examCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                    this.style.boxShadow = '0 8px 16px rgba(0,0,0,0.2)';
                });

                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = 'none';
                });
            });
        });

        // Mostrar mensagem de boas-vindas para usuários logados
        <?php if ($usuario_logado && isset($_GET['login']) && $_GET['login'] === 'success'): ?>
            setTimeout(function() {
                alert('Bem-vindo de volta, <?php echo htmlspecialchars($usuario_nome); ?>! 🎓\n\nVocê pode acessar os simulados e ver seu histórico de provas.');
            }, 1000);
        <?php endif; ?>
    </script>
</body>
</html>
