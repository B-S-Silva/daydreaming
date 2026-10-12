<?php
// Sistema de Gerenciamento de Badges e Níveis
// Este arquivo contém funções para gerenciar badges e sistema de níveis

require_once 'config.php';

class BadgesManager {
    private $pdo;
    
    public function __construct() {
        $this->pdo = conectarBD();
    }
    
    /**
     * Verifica e concede badges baseadas no resultado do teste
     */
    public function verificarBadgesResultado($usuario_id, $pontuacao, $tipo_prova) {
        $badges_conquistadas = [];
        
        try {
            // Badge por pontuação
            if ($pontuacao >= 90) {
                $badges_conquistadas[] = $this->concederBadge($usuario_id, 'excelencia');
            } elseif ($pontuacao >= 80) {
                $badges_conquistadas[] = $this->concederBadge($usuario_id, 'muito_bom');
            } elseif ($pontuacao >= 70) {
                $badges_conquistadas[] = $this->concederBadge($usuario_id, 'bom');
            } elseif ($pontuacao >= 60) {
                $badges_conquistadas[] = $this->concederBadge($usuario_id, 'satisfatorio');
            }
            
            // Badge de 100%
            if ($pontuacao == 100) {
                $badges_conquistadas[] = $this->concederBadge($usuario_id, 'perfeccionista');
            }
            
            // Badge de primeiro teste
            $total_testes = $this->contarTestes($usuario_id);
            if ($total_testes == 1) {
                $badges_conquistadas[] = $this->concederBadge($usuario_id, 'primeiro_teste');
            }
            
            // Badges por frequência de tipo de prova
            $testes_tipo = $this->contarTestesPorTipo($usuario_id, $tipo_prova);
            if ($testes_tipo == 5) {
                $badge_codigo = 'especialista_' . $tipo_prova;
                $badges_conquistadas[] = $this->concederBadge($usuario_id, $badge_codigo);
            }
            
            // Badges por consistência
            $resultados_bons = $this->contarResultadosAcimaDe($usuario_id, 70);
            if ($resultados_bons == 5) {
                $badges_conquistadas[] = $this->concederBadge($usuario_id, 'consistente');
            } elseif ($resultados_bons == 10) {
                $badges_conquistadas[] = $this->concederBadge($usuario_id, 'dedicado');
            }
            
            // Badges por total de testes
            if ($total_testes == 20) {
                $badges_conquistadas[] = $this->concederBadge($usuario_id, 'maratonista');
            } elseif ($total_testes == 50) {
                $badges_conquistadas[] = $this->concederBadge($usuario_id, 'persistente');
            }
            
            return array_filter($badges_conquistadas); // Remove nulls
            
        } catch (Exception $e) {
            error_log("Erro ao verificar badges: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Concede uma badge específica ao usuário
     */
    private function concederBadge($usuario_id, $badge_codigo) {
        try {
            // Verifica se o usuário já possui a badge
            $stmt = $this->pdo->prepare("
                SELECT ub.id 
                FROM usuario_badges ub 
                JOIN badges b ON ub.badge_id = b.id 
                WHERE ub.usuario_id = ? AND b.codigo = ?
            ");
            $stmt->execute([$usuario_id, $badge_codigo]);
            
            if ($stmt->rowCount() > 0) {
                return null; // Usuário já possui a badge
            }
            
            // Busca a badge
            $stmt = $this->pdo->prepare("SELECT id, nome, icone FROM badges WHERE codigo = ? AND ativo = 1");
            $stmt->execute([$badge_codigo]);
            $badge = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$badge) {
                return null; // Badge não encontrada
            }
            
            // Concede a badge
            $stmt = $this->pdo->prepare("
                INSERT INTO usuario_badges (usuario_id, badge_id, data_conquista) 
                VALUES (?, ?, NOW())
            ");
            $stmt->execute([$usuario_id, $badge['id']]);
            
            // Adiciona experiência pela conquista da badge
            $this->adicionarExperiencia($usuario_id, 50);
            
            return [
                'id' => $badge['id'],
                'nome' => $badge['nome'],
                'icone' => $badge['icone']
            ];
            
        } catch (Exception $e) {
            error_log("Erro ao conceder badge {$badge_codigo}: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Adiciona experiência ao usuário e verifica se subiu de nível
     */
    public function adicionarExperiencia($usuario_id, $experiencia) {
        try {
            // Busca ou cria registro de nível do usuário
            $stmt = $this->pdo->prepare("SELECT * FROM niveis_usuario WHERE usuario_id = ?");
            $stmt->execute([$usuario_id]);
            $nivel_data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$nivel_data) {
                // Cria registro inicial
                $stmt = $this->pdo->prepare("
                    INSERT INTO niveis_usuario (usuario_id, nivel_atual, experiencia_total, experiencia_nivel, experiencia_necessaria) 
                    VALUES (?, 1, ?, ?, 100)
                ");
                $stmt->execute([$usuario_id, $experiencia, $experiencia]);
                return ['subiu_nivel' => false, 'nivel_atual' => 1];
            }
            
            $nova_exp_total = $nivel_data['experiencia_total'] + $experiencia;
            $nova_exp_nivel = $nivel_data['experiencia_nivel'] + $experiencia;
            $nivel_atual = $nivel_data['nivel_atual'];
            $exp_necessaria = $nivel_data['experiencia_necessaria'];
            
            $subiu_nivel = false;
            
            // Verifica se subiu de nível
            while ($nova_exp_nivel >= $exp_necessaria) {
                $nova_exp_nivel -= $exp_necessaria;
                $nivel_atual++;
                $exp_necessaria = $this->calcularExperienciaNecessaria($nivel_atual);
                $subiu_nivel = true;
            }
            
            // Atualiza no banco
            $stmt = $this->pdo->prepare("
                UPDATE niveis_usuario 
                SET experiencia_total = ?, experiencia_nivel = ?, nivel_atual = ?, experiencia_necessaria = ?
                WHERE usuario_id = ?
            ");
            $stmt->execute([$nova_exp_total, $nova_exp_nivel, $nivel_atual, $exp_necessaria, $usuario_id]);
            
            return [
                'subiu_nivel' => $subiu_nivel,
                'nivel_atual' => $nivel_atual,
                'experiencia_total' => $nova_exp_total,
                'experiencia_nivel' => $nova_exp_nivel,
                'experiencia_necessaria' => $exp_necessaria
            ];
            
        } catch (Exception $e) {
            error_log("Erro ao adicionar experiência: " . $e->getMessage());
            return ['subiu_nivel' => false, 'nivel_atual' => 1];
        }
    }
    
    /**
     * Calcula experiência necessária para o próximo nível
     */
    private function calcularExperienciaNecessaria($nivel) {
        // Fórmula: 100 * (1.2 ^ (nivel - 1))
        return ceil(100 * pow(1.2, $nivel - 1));
    }
    
    /**
     * Conta total de testes do usuário
     */
    private function contarTestes($usuario_id) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM resultados_testes WHERE usuario_id = ?");
        $stmt->execute([$usuario_id]);
        return $stmt->fetchColumn();
    }
    
    /**
     * Conta testes por tipo de prova
     */
    private function contarTestesPorTipo($usuario_id, $tipo_prova) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM resultados_testes WHERE usuario_id = ? AND tipo_prova = ?");
        $stmt->execute([$usuario_id, $tipo_prova]);
        return $stmt->fetchColumn();
    }
    
    /**
     * Conta resultados acima de uma pontuação
     */
    private function contarResultadosAcimaDe($usuario_id, $pontuacao_minima) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM resultados_testes WHERE usuario_id = ? AND pontuacao >= ?");
        $stmt->execute([$usuario_id, $pontuacao_minima]);
        return $stmt->fetchColumn();
    }
    
    /**
     * Busca badges do usuário
     */
    public function getBadgesUsuario($usuario_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT b.nome, b.descricao, b.icone, ub.data_conquista
                FROM usuario_badges ub
                JOIN badges b ON ub.badge_id = b.id
                WHERE ub.usuario_id = ?
                ORDER BY ub.data_conquista DESC
            ");
            $stmt->execute([$usuario_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erro ao buscar badges do usuário: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Busca informações de nível do usuário
     */
    public function getNivelUsuario($usuario_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM niveis_usuario WHERE usuario_id = ?");
            $stmt->execute([$usuario_id]);
            $nivel = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$nivel) {
                // Cria registro inicial se não existir
                $stmt = $this->pdo->prepare("
                    INSERT INTO niveis_usuario (usuario_id, nivel_atual, experiencia_total, experiencia_nivel, experiencia_necessaria) 
                    VALUES (?, 1, 0, 0, 100)
                ");
                $stmt->execute([$usuario_id]);
                
                return [
                    'nivel_atual' => 1,
                    'experiencia_total' => 0,
                    'experiencia_nivel' => 0,
                    'experiencia_necessaria' => 100,
                    'progresso_percentual' => 0
                ];
            }
            
            $nivel['progresso_percentual'] = ($nivel['experiencia_nivel'] / $nivel['experiencia_necessaria']) * 100;
            return $nivel;
            
        } catch (Exception $e) {
            error_log("Erro ao buscar nível do usuário: " . $e->getMessage());
            return [
                'nivel_atual' => 1,
                'experiencia_total' => 0,
                'experiencia_nivel' => 0,
                'experiencia_necessaria' => 100,
                'progresso_percentual' => 0
            ];
        }
    }
    
    /**
     * Processa resultado do teste e concede experiência e badges
     */
    public function processarResultadoTeste($usuario_id, $pontuacao, $tipo_prova, $tempo_gasto, $acertos, $total_questoes) {
        try {
            // Calcula experiência baseada no desempenho
            $exp_base = 20; // Experiência base por completar teste
            $exp_pontuacao = floor($pontuacao / 10) * 5; // 5 exp por cada 10% de pontuação
            $exp_bonus = 0;
            
            // Bônus por alta pontuação
            if ($pontuacao >= 90) $exp_bonus += 30;
            elseif ($pontuacao >= 80) $exp_bonus += 20;
            elseif ($pontuacao >= 70) $exp_bonus += 10;
            
            // Bônus por velocidade (se completou em menos de 80% do tempo)
            $tempo_limite = $this->getTempoLimite($tipo_prova);
            if ($tempo_gasto < ($tempo_limite * 0.8)) {
                $exp_bonus += 15;
                // Verifica badge de velocista
                $this->concederBadge($usuario_id, 'rapido');
            }
            
            $experiencia_total = $exp_base + $exp_pontuacao + $exp_bonus;
            
            // Adiciona experiência
            $resultado_nivel = $this->adicionarExperiencia($usuario_id, $experiencia_total);
            
            // Verifica e concede badges
            $badges_conquistadas = $this->verificarBadgesResultado($usuario_id, $pontuacao, $tipo_prova);
            
            return [
                'experiencia_ganha' => $experiencia_total,
                'nivel_info' => $resultado_nivel,
                'badges_conquistadas' => $badges_conquistadas
            ];
            
        } catch (Exception $e) {
            error_log("Erro ao processar resultado do teste: " . $e->getMessage());
            return [
                'experiencia_ganha' => 0,
                'nivel_info' => ['subiu_nivel' => false, 'nivel_atual' => 1],
                'badges_conquistadas' => []
            ];
        }
    }
    
    /**
     * Retorna tempo limite padrão por tipo de prova (em segundos)
     */
    private function getTempoLimite($tipo_prova) {
        $tempos = [
            'toefl' => 10800,     // 180 minutos
        'ielts' => 9900,      // 165 minutos
        'sat' => 10800,       // 180 minutos
        'dele' => 14400,      // 240 minutos
        'delf' => 9000,       // 150 minutos
        'testdaf' => 11400,   // 190 minutos
        'jlpt' => 10200,      // 170 minutos
        'hsk' => 9000         // 150 minutos
        ];
        
        return $tempos[$tipo_prova] ?? 3600;
    }
}

// Função auxiliar para usar em outros arquivos
function processarResultadoCompleto($usuario_id, $pontuacao, $tipo_prova, $tempo_gasto, $acertos, $total_questoes) {
    $badges_manager = new BadgesManager();
    return $badges_manager->processarResultadoTeste($usuario_id, $pontuacao, $tipo_prova, $tempo_gasto, $acertos, $total_questoes);
}

function getBadgesUsuario($usuario_id) {
    $badges_manager = new BadgesManager();
    return $badges_manager->getBadgesUsuario($usuario_id);
}

function getNivelUsuario($usuario_id) {
    $badges_manager = new BadgesManager();
    return $badges_manager->getNivelUsuario($usuario_id);
}
?>