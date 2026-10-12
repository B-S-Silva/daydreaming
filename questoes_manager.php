<?php
// Sistema de Gerenciamento de Questões
// Este arquivo contém funções para carregar e gerenciar questões de arquivos JSON/XML

require_once 'config.php';

class QuestoesManager {
    private $pdo;
    
    public function __construct() {
        $this->pdo = conectarBD();
    }
    
    /**
     * Carrega questões de um arquivo JSON
     */
    public function carregarQuestoesJSON($arquivo_path, $tipo_prova) {
        try {
            if (!file_exists($arquivo_path)) {
                throw new Exception("Arquivo não encontrado: {$arquivo_path}");
            }
            
            $json_content = file_get_contents($arquivo_path);
            $questoes_data = json_decode($json_content, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Erro ao decodificar JSON: " . json_last_error_msg());
            }
            
            return $this->processarQuestoes($questoes_data, $tipo_prova);
            
        } catch (Exception $e) {
            error_log("Erro ao carregar questões JSON: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Carrega questões de um arquivo XML
     */
    public function carregarQuestoesXML($arquivo_path, $tipo_prova) {
        try {
            if (!file_exists($arquivo_path)) {
                throw new Exception("Arquivo não encontrado: {$arquivo_path}");
            }
            
            $xml = simplexml_load_file($arquivo_path);
            if ($xml === false) {
                throw new Exception("Erro ao carregar arquivo XML");
            }
            
            // Converte XML para array
            $questoes_data = json_decode(json_encode($xml), true);
            
            return $this->processarQuestoes($questoes_data, $tipo_prova);
            
        } catch (Exception $e) {
            error_log("Erro ao carregar questões XML: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Processa e insere questões no banco de dados
     */
    private function processarQuestoes($questoes_data, $tipo_prova) {
        try {
            $this->pdo->beginTransaction();
            
            $questoes_inseridas = 0;
            $questoes_atualizadas = 0;
            
            // Verifica se é um array de questões ou se tem uma estrutura específica
            $questoes = isset($questoes_data['questoes']) ? $questoes_data['questoes'] : $questoes_data;
            
            foreach ($questoes as $questao) {
                $resultado = $this->inserirOuAtualizarQuestao($questao, $tipo_prova);
                if ($resultado === 'inserida') {
                    $questoes_inseridas++;
                } elseif ($resultado === 'atualizada') {
                    $questoes_atualizadas++;
                }
            }
            
            $this->pdo->commit();
            
            return [
                'sucesso' => true,
                'questoes_inseridas' => $questoes_inseridas,
                'questoes_atualizadas' => $questoes_atualizadas,
                'total_processadas' => $questoes_inseridas + $questoes_atualizadas
            ];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Erro ao processar questões: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Insere ou atualiza uma questão específica
     */
    private function inserirOuAtualizarQuestao($questao, $tipo_prova) {
        try {
            // Validação dos campos obrigatórios
            $campos_obrigatorios = ['numero_questao', 'enunciado', 'alternativa_a', 'alternativa_b', 
                                  'alternativa_c', 'alternativa_d', 'alternativa_e', 'resposta_correta'];
            
            foreach ($campos_obrigatorios as $campo) {
                if (!isset($questao[$campo]) || empty($questao[$campo])) {
                    throw new Exception("Campo obrigatório ausente: {$campo}");
                }
            }
            
            // Verifica se a questão já existe
            $stmt = $this->pdo->prepare("
                SELECT id FROM questoes 
                WHERE tipo_prova = ? AND numero_questao = ?
            ");
            $stmt->execute([$tipo_prova, $questao['numero_questao']]);
            $questao_existente = $stmt->fetch();
            
            if ($questao_existente) {
                // Atualiza questão existente
                $stmt = $this->pdo->prepare("
                    UPDATE questoes SET
                        enunciado = ?,
                        alternativa_a = ?,
                        alternativa_b = ?,
                        alternativa_c = ?,
                        alternativa_d = ?,
                        alternativa_e = ?,
                        resposta_correta = ?,
                        dificuldade = ?,
                        materia = ?,
                        assunto = ?,
                        explicacao = ?,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                
                $stmt->execute([
                    $questao['enunciado'],
                    $questao['alternativa_a'],
                    $questao['alternativa_b'],
                    $questao['alternativa_c'],
                    $questao['alternativa_d'],
                    $questao['alternativa_e'],
                    $questao['resposta_correta'],
                    $questao['dificuldade'] ?? 'medio',
                    $questao['materia'] ?? null,
                    $questao['assunto'] ?? null,
                    $questao['explicacao'] ?? null,
                    $questao_existente['id']
                ]);
                
                return 'atualizada';
                
            } else {
                // Insere nova questão
                $stmt = $this->pdo->prepare("
                    INSERT INTO questoes (
                        tipo_prova, numero_questao, enunciado, alternativa_a, alternativa_b,
                        alternativa_c, alternativa_d, alternativa_e, resposta_correta,
                        dificuldade, materia, assunto, explicacao
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $tipo_prova,
                    $questao['numero_questao'],
                    $questao['enunciado'],
                    $questao['alternativa_a'],
                    $questao['alternativa_b'],
                    $questao['alternativa_c'],
                    $questao['alternativa_d'],
                    $questao['alternativa_e'],
                    $questao['resposta_correta'],
                    $questao['dificuldade'] ?? 'medio',
                    $questao['materia'] ?? null,
                    $questao['assunto'] ?? null,
                    $questao['explicacao'] ?? null
                ]);
                
                return 'inserida';
            }
            
        } catch (Exception $e) {
            error_log("Erro ao inserir/atualizar questão: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Busca questões para um teste específico
     */
    public function buscarQuestoesTeste($tipo_prova, $quantidade = 20, $dificuldade = null) {
        try {
            $sql = "SELECT * FROM questoes WHERE tipo_prova = ? AND ativo = 1";
            $params = [$tipo_prova];
            
            if ($dificuldade) {
                $sql .= " AND dificuldade = ?";
                $params[] = $dificuldade;
            }
            
            $sql .= " ORDER BY RAND() LIMIT ?";
            $params[] = $quantidade;
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Erro ao buscar questões: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Conta questões disponíveis por tipo
     */
    public function contarQuestoes($tipo_prova = null) {
        try {
            if ($tipo_prova) {
                $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM questoes WHERE tipo_prova = ? AND ativo = 1");
                $stmt->execute([$tipo_prova]);
                return $stmt->fetchColumn();
            } else {
                $stmt = $this->pdo->prepare("
                    SELECT tipo_prova, COUNT(*) as total 
                    FROM questoes 
                    WHERE ativo = 1 
                    GROUP BY tipo_prova
                ");
                $stmt->execute();
                return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            }
        } catch (Exception $e) {
            error_log("Erro ao contar questões: " . $e->getMessage());
            return $tipo_prova ? 0 : [];
        }
    }
    
    /**
     * Valida estrutura de arquivo JSON de questões
     */
    public function validarEstruturaJSON($arquivo_path) {
        try {
            $json_content = file_get_contents($arquivo_path);
            $data = json_decode($json_content, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return ['valido' => false, 'erro' => 'JSON inválido: ' . json_last_error_msg()];
            }
            
            $questoes = isset($data['questoes']) ? $data['questoes'] : $data;
            
            if (!is_array($questoes)) {
                return ['valido' => false, 'erro' => 'Estrutura deve conter um array de questões'];
            }
            
            $campos_obrigatorios = ['numero_questao', 'enunciado', 'alternativa_a', 'alternativa_b', 
                                  'alternativa_c', 'alternativa_d', 'alternativa_e', 'resposta_correta'];
            
            foreach ($questoes as $index => $questao) {
                foreach ($campos_obrigatorios as $campo) {
                    if (!isset($questao[$campo])) {
                        return [
                            'valido' => false, 
                            'erro' => "Campo '{$campo}' ausente na questão " . ($index + 1)
                        ];
                    }
                }
                
                // Valida resposta correta
                if (!in_array($questao['resposta_correta'], ['a', 'b', 'c', 'd', 'e'])) {
                    return [
                        'valido' => false, 
                        'erro' => "Resposta correta inválida na questão " . ($index + 1) . ". Deve ser a, b, c, d ou e"
                    ];
                }
            }
            
            return [
                'valido' => true, 
                'total_questoes' => count($questoes),
                'estrutura' => 'JSON válido com ' . count($questoes) . ' questões'
            ];
            
        } catch (Exception $e) {
            return ['valido' => false, 'erro' => 'Erro ao validar arquivo: ' . $e->getMessage()];
        }
    }
    
    /**
     * Gera arquivo de exemplo JSON
     */
    public function gerarExemploJSON($tipo_prova) {
        $exemplo = [
            'metadata' => [
                'tipo_prova' => $tipo_prova,
                'versao' => '1.0',
                'data_criacao' => date('Y-m-d H:i:s'),
                'total_questoes' => 2
            ],
            'questoes' => [
                [
                    'numero_questao' => 1,
                    'enunciado' => 'Esta é uma questão de exemplo. Qual é a resposta correta?',
                    'alternativa_a' => 'Primeira alternativa',
                    'alternativa_b' => 'Segunda alternativa',
                    'alternativa_c' => 'Terceira alternativa',
                    'alternativa_d' => 'Quarta alternativa',
                    'alternativa_e' => 'Quinta alternativa',
                    'resposta_correta' => 'a',
                    'dificuldade' => 'medio',
                    'materia' => 'Exemplo',
                    'assunto' => 'Questão de exemplo',
                    'explicacao' => 'Esta é uma explicação da resposta correta.'
                ],
                [
                    'numero_questao' => 2,
                    'enunciado' => 'Segunda questão de exemplo para demonstrar a estrutura.',
                    'alternativa_a' => 'Opção A',
                    'alternativa_b' => 'Opção B',
                    'alternativa_c' => 'Opção C',
                    'alternativa_d' => 'Opção D',
                    'alternativa_e' => 'Opção E',
                    'resposta_correta' => 'b',
                    'dificuldade' => 'facil',
                    'materia' => 'Exemplo',
                    'assunto' => 'Estrutura de dados',
                    'explicacao' => 'Explicação detalhada da segunda questão.'
                ]
            ]
        ];
        
        return json_encode($exemplo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}

// Funções auxiliares para usar em outros arquivos
function carregarQuestoesDeArquivo($arquivo_path, $tipo_prova) {
    $manager = new QuestoesManager();
    
    $extensao = strtolower(pathinfo($arquivo_path, PATHINFO_EXTENSION));
    
    if ($extensao === 'json') {
        return $manager->carregarQuestoesJSON($arquivo_path, $tipo_prova);
    } elseif ($extensao === 'xml') {
        return $manager->carregarQuestoesXML($arquivo_path, $tipo_prova);
    } else {
        return false;
    }
}

function buscarQuestoesPorTipo($tipo_prova, $quantidade = 20) {
    $manager = new QuestoesManager();
    return $manager->buscarQuestoesTeste($tipo_prova, $quantidade);
}

function contarQuestoesDisponiveis($tipo_prova = null) {
    $manager = new QuestoesManager();
    return $manager->contarQuestoes($tipo_prova);
}

function validarArquivoQuestoes($arquivo_path) {
    $manager = new QuestoesManager();
    return $manager->validarEstruturaJSON($arquivo_path);
}

function gerarExemploQuestoes($tipo_prova) {
    $manager = new QuestoesManager();
    return $manager->gerarExemploJSON($tipo_prova);
}
?>