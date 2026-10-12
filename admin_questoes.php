<?php
session_start();
require_once 'config.php';
require_once 'questoes_manager.php';
require_once 'verificar_auth.php';

// Verifica se é administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$pdo = conectarBD();
$questoes_manager = new QuestoesManager();
$mensagem = '';
$tipo_mensagem = '';

// Processa upload de arquivo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_questoes'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $mensagem = 'Token CSRF inválido.';
        $tipo_mensagem = 'error';
    } else {
        $tipo_prova = $_POST['tipo_prova'];
        
        if (isset($_FILES['arquivo_questoes']) && $_FILES['arquivo_questoes']['error'] === UPLOAD_ERR_OK) {
            $arquivo_temp = $_FILES['arquivo_questoes']['tmp_name'];
            $nome_arquivo = $_FILES['arquivo_questoes']['name'];
            $extensao = strtolower(pathinfo($nome_arquivo, PATHINFO_EXTENSION));
            
            if (!in_array($extensao, ['json', 'xml'])) {
                $mensagem = 'Apenas arquivos JSON e XML são permitidos.';
                $tipo_mensagem = 'error';
            } else {
                // Valida estrutura antes de processar
                if ($extensao === 'json') {
                    $validacao = $questoes_manager->validarEstruturaJSON($arquivo_temp);
                    if (!$validacao['valido']) {
                        $mensagem = 'Arquivo inválido: ' . $validacao['erro'];
                        $tipo_mensagem = 'error';
                    } else {
                        $resultado = $questoes_manager->carregarQuestoesJSON($arquivo_temp, $tipo_prova);
                        if ($resultado) {
                            $mensagem = "Questões carregadas com sucesso! {$resultado['questoes_inseridas']} inseridas, {$resultado['questoes_atualizadas']} atualizadas.";
                            $tipo_mensagem = 'success';
                        } else {
                            $mensagem = 'Erro ao carregar questões do arquivo.';
                            $tipo_mensagem = 'error';
                        }
                    }
                } else {
                    $resultado = $questoes_manager->carregarQuestoesXML($arquivo_temp, $tipo_prova);
                    if ($resultado) {
                        $mensagem = "Questões carregadas com sucesso! {$resultado['questoes_inseridas']} inseridas, {$resultado['questoes_atualizadas']} atualizadas.";
                        $tipo_mensagem = 'success';
                    } else {
                        $mensagem = 'Erro ao carregar questões do arquivo XML.';
                        $tipo_mensagem = 'error';
                    }
                }
            }
        } else {
            $mensagem = 'Erro no upload do arquivo.';
            $tipo_mensagem = 'error';
        }
    }
}

// Gera arquivo de exemplo
if (isset($_GET['gerar_exemplo'])) {
    $tipo_prova = $_GET['tipo_prova'] ?? 'toefl';
    $exemplo_json = $questoes_manager->gerarExemploJSON($tipo_prova);
    
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="exemplo_questoes_' . $tipo_prova . '.json"');
    echo $exemplo_json;
    exit;
}

// Busca estatísticas
$estatisticas = $questoes_manager->contarQuestoes();

// Busca questões recentes
$stmt = $pdo->prepare("
    SELECT tipo_prova, COUNT(*) as total, MAX(created_at) as ultima_atualizacao
    FROM questoes 
    WHERE ativo = 1 
    GROUP BY tipo_prova
    ORDER BY ultima_atualizacao DESC
");
$stmt->execute();
$questoes_por_tipo = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administração de Questões - <?= NOME_SITE ?></title>
    <link rel="stylesheet" href="public/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .admin-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .admin-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: var(--shadow-light);
            text-align: center;
            border-left: 4px solid var(--primary-color);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .upload-section {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: var(--shadow-medium);
            margin-bottom: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-dark);
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .file-upload {
            position: relative;
            display: inline-block;
            width: 100%;
        }
        
        .file-upload input[type=file] {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        
        .file-upload-label {
            display: block;
            padding: 2rem;
            border: 2px dashed var(--border-color);
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .file-upload-label:hover {
            border-color: var(--primary-color);
            background-color: var(--background-light);
        }
        
        .questoes-table {
            background: white;
            border-radius: 15px;
            box-shadow: var(--shadow-medium);
            overflow: hidden;
        }
        
        .table-header {
            background: var(--primary-color);
            color: white;
            padding: 1rem 2rem;
            font-weight: 600;
        }
        
        .table-content {
            padding: 1rem;
        }
        
        .questao-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr;
            gap: 1rem;
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            align-items: center;
        }
        
        .questao-row:last-child {
            border-bottom: none;
        }
        
        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .badge-toefl { background: #e8f5e8; color: #388e3c; }
        .badge-ielts { background: #e3f2fd; color: #1976d2; }
        .badge-sat { background: #fff3e0; color: #f57c00; }
        .badge-dele { background: #fce4ec; color: #c2185b; }
        .badge-delf { background: #e8eaf6; color: #303f9f; }
        .badge-testdaf { background: #efebe9; color: #5d4037; }
        .badge-jlpt { background: #f3e5f5; color: #7b1fa2; }
        .badge-hsk { background: #ffebee; color: #d32f2f; }
        
        .btn-group {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        
        .exemplo-links {
            margin-top: 1rem;
            padding: 1rem;
            background: var(--background-light);
            border-radius: 8px;
        }
        
        .exemplo-links h4 {
            margin-bottom: 0.5rem;
            color: var(--text-dark);
        }
        
        .exemplo-link {
            display: inline-block;
            margin-right: 1rem;
            margin-bottom: 0.5rem;
            padding: 0.5rem 1rem;
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            text-decoration: none;
            color: var(--text-dark);
            transition: all 0.3s ease;
        }
        
        .exemplo-link:hover {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        @media (max-width: 768px) {
            .questao-row {
                grid-template-columns: 1fr;
                text-align: center;
            }
            
            .btn-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1><i class="fas fa-cogs"></i> Administração de Questões</h1>
            <p>Gerencie questões dos simulados e faça upload de novos conteúdos</p>
        </div>
        
        <?php if ($mensagem): ?>
            <div class="alert alert-<?= $tipo_mensagem ?>">
                <i class="fas fa-<?= $tipo_mensagem === 'success' ? 'check-circle' : 'exclamation-triangle' ?>"></i>
                <?= htmlspecialchars($mensagem) ?>
            </div>
        <?php endif; ?>
        
        <!-- Estatísticas -->
        <div class="stats-grid">
            <?php foreach ($questoes_por_tipo as $tipo): ?>
                <div class="stat-card">
                    <div class="stat-number"><?= $tipo['total'] ?></div>
                    <div class="stat-label">
                        <span class="badge badge-<?= $tipo['tipo_prova'] ?>">
                            <?= strtoupper($tipo['tipo_prova']) ?>
                        </span>
                    </div>
                    <small class="text-muted">
                        Última atualização: <?= date('d/m/Y H:i', strtotime($tipo['ultima_atualizacao'])) ?>
                    </small>
                </div>
            <?php endforeach; ?>
            
            <div class="stat-card">
                <div class="stat-number"><?= array_sum($estatisticas) ?></div>
                <div class="stat-label">Total de Questões</div>
                <small class="text-muted">Em todas as categorias</small>
            </div>
        </div>
        
        <!-- Upload de Questões -->
        <div class="upload-section">
            <h2><i class="fas fa-upload"></i> Upload de Questões</h2>
            <p class="text-muted">Faça upload de arquivos JSON ou XML contendo questões para os simulados.</p>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                
                <div class="form-group">
                    <label for="tipo_prova">Tipo de Prova:</label>
                    <select name="tipo_prova" id="tipo_prova" class="form-control" required>
                        <option value="toefl">TOEFL</option>
                        <option value="ielts">IELTS</option>
                        <option value="sat">SAT</option>
                        <option value="dele">DELE</option>
                        <option value="delf">DELF</option>
                        <option value="testdaf">TestDaF</option>
                        <option value="jlpt">JLPT</option>
                        <option value="hsk">HSK</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Arquivo de Questões (JSON ou XML):</label>
                    <div class="file-upload">
                        <input type="file" name="arquivo_questoes" accept=".json,.xml" required>
                        <label class="file-upload-label">
                            <i class="fas fa-cloud-upload-alt fa-2x"></i>
                            <div>Clique aqui ou arraste o arquivo</div>
                            <small>Formatos aceitos: JSON, XML (máx. 10MB)</small>
                        </label>
                    </div>
                </div>
                
                <div class="btn-group">
                    <button type="submit" name="upload_questoes" class="btn btn-primary">
                        <i class="fas fa-upload"></i> Fazer Upload
                    </button>
                    <a href="admin_forum.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Voltar ao Admin
                    </a>
                </div>
            </form>
            
            <!-- Links para exemplos -->
            <div class="exemplo-links">
                <h4><i class="fas fa-download"></i> Baixar Exemplos de Estrutura:</h4>
                <a href="?gerar_exemplo&tipo_prova=toefl" class="exemplo-link">
                        <i class="fas fa-file-code"></i> Exemplo TOEFL (JSON)
                    </a>
                    <a href="?gerar_exemplo&tipo_prova=ielts" class="exemplo-link">
                        <i class="fas fa-file-code"></i> Exemplo IELTS (JSON)
                    </a>
                    <a href="?gerar_exemplo&tipo_prova=sat" class="exemplo-link">
                        <i class="fas fa-file-code"></i> Exemplo SAT (JSON)
                    </a>
                    <a href="?gerar_exemplo&tipo_prova=dele" class="exemplo-link">
                        <i class="fas fa-file-code"></i> Exemplo DELE (JSON)
                    </a>
                    <a href="?gerar_exemplo&tipo_prova=delf" class="exemplo-link">
                        <i class="fas fa-file-code"></i> Exemplo DELF (JSON)
                    </a>
                    <a href="?gerar_exemplo&tipo_prova=testdaf" class="exemplo-link">
                        <i class="fas fa-file-code"></i> Exemplo TestDaF (JSON)
                    </a>
                    <a href="?gerar_exemplo&tipo_prova=jlpt" class="exemplo-link">
                        <i class="fas fa-file-code"></i> Exemplo JLPT (JSON)
                    </a>
                    <a href="?gerar_exemplo&tipo_prova=hsk" class="exemplo-link">
                         <i class="fas fa-file-code"></i> Exemplo HSK (JSON)
                     </a>
            </div>
        </div>
        
        <!-- Tabela de Questões -->
        <div class="questoes-table">
            <div class="table-header">
                <i class="fas fa-list"></i> Questões por Categoria
            </div>
            <div class="table-content">
                <?php if (empty($questoes_por_tipo)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Nenhuma questão cadastrada ainda.</p>
                        <p class="text-muted">Faça upload de um arquivo para começar.</p>
                    </div>
                <?php else: ?>
                    <div class="questao-row" style="font-weight: 600; background: var(--background-light);">
                        <div>Tipo de Prova</div>
                        <div>Total de Questões</div>
                        <div>Última Atualização</div>
                        <div>Ações</div>
                    </div>
                    <?php foreach ($questoes_por_tipo as $tipo): ?>
                        <div class="questao-row">
                            <div>
                                <span class="badge badge-<?= $tipo['tipo_prova'] ?>">
                                    <?= strtoupper($tipo['tipo_prova']) ?>
                                </span>
                            </div>
                            <div><?= $tipo['total'] ?> questões</div>
                            <div><?= date('d/m/Y H:i', strtotime($tipo['ultima_atualizacao'])) ?></div>
                            <div>
                                <a href="simulador_provas.php?tipo=<?= $tipo['tipo_prova'] ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-play"></i> Testar
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        // Melhora a experiência do upload de arquivo
        document.addEventListener('DOMContentLoaded', function() {
            const fileInput = document.querySelector('input[type="file"]');
            const fileLabel = document.querySelector('.file-upload-label');
            
            fileInput.addEventListener('change', function() {
                if (this.files.length > 0) {
                    const fileName = this.files[0].name;
                    fileLabel.innerHTML = `
                        <i class="fas fa-file-check fa-2x"></i>
                        <div>Arquivo selecionado: ${fileName}</div>
                        <small>Clique em "Fazer Upload" para enviar</small>
                    `;
                    fileLabel.style.borderColor = 'var(--success-color)';
                    fileLabel.style.backgroundColor = 'var(--success-light)';
                }
            });
            
            // Drag and drop
            fileLabel.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.style.borderColor = 'var(--primary-color)';
                this.style.backgroundColor = 'var(--background-light)';
            });
            
            fileLabel.addEventListener('dragleave', function(e) {
                e.preventDefault();
                this.style.borderColor = 'var(--border-color)';
                this.style.backgroundColor = 'transparent';
            });
            
            fileLabel.addEventListener('drop', function(e) {
                e.preventDefault();
                this.style.borderColor = 'var(--border-color)';
                this.style.backgroundColor = 'transparent';
                
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    fileInput.files = files;
                    fileInput.dispatchEvent(new Event('change'));
                }
            });
        });
    </script>
</body>
</html>