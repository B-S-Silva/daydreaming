<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Executando: <?php echo $prova['nome']; ?></title>
    <link rel="stylesheet" href="public/css/style.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #f5f5f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .teste-interface {
            display: flex;
            height: 100vh;
            overflow: hidden;
        }
        
        .sidebar {
            width: 300px;
            background: white;
            border-right: 1px solid #ddd;
            display: flex;
            flex-direction: column;
        }
        
        .sidebar-header {
            padding: 20px;
            background: <?php echo $prova['cor']; ?>;
            color: white;
            text-align: center;
        }
        
        .sidebar-header h2 {
            margin: 0 0 10px 0;
            font-size: 1.2rem;
        }
        
        .cronometro {
            font-size: 1.5rem;
            font-weight: bold;
            font-family: 'Courier New', monospace;
        }
        
        .navegacao-questoes {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
        }
        
        .grid-questoes {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 8px;
        }
        
        .btn-questao {
            width: 40px;
            height: 40px;
            border: 2px solid #ddd;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        
        .btn-questao:hover {
            border-color: <?php echo $prova['cor']; ?>;
        }
        
        .btn-questao.atual {
            background: <?php echo $prova['cor']; ?>;
            color: white;
            border-color: <?php echo $prova['cor']; ?>;
        }
        
        .btn-questao.respondida {
            background: #4CAF50;
            color: white;
            border-color: #4CAF50;
        }
        

        
        .legenda {
            margin-top: 20px;
            font-size: 0.9rem;
        }
        
        .legenda-item {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .legenda-cor {
            width: 16px;
            height: 16px;
            border-radius: 4px;
            margin-right: 8px;
        }
        
        .area-questao {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: white;
        }
        
        .questao-header {
            padding: 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
        }

        .questao-numero {
            font-size: 1.2rem;
            font-weight: bold;
            color: <?php echo $prova['cor']; ?>;
            flex: 0 0 auto;
        }

        .questao-assunto {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            font-size: 1rem;
            font-weight: 600;
            color: #555;
            background: #fff;
            padding: 8px 16px;
            border-radius: 20px;
            border: 2px solid <?php echo $prova['cor']; ?>;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            max-width: 300px;
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .questao-acoes {
            display: flex;
            gap: 10px;
        }
        
        .btn-voltar {
            padding: 8px 16px;
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .btn-voltar:hover {
            background: #5a6268;
            transform: translateY(-1px);
            color: white;
            text-decoration: none;
        }
        
        .questao-conteudo {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
        }
        
        .enunciado {
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 30px;
            color: #333;
        }
        
        .alternativas {
            margin-bottom: 30px;
        }

        .alternativa {
            display: flex;
            align-items: flex-start;
            margin-bottom: 15px;
            padding: 15px;
            border: 2px solid #f0f0f0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .alternativa:hover {
            border-color: <?php echo $prova['cor']; ?>;
            background: #f8f9ff;
        }

        .alternativa.selecionada {
            border-color: <?php echo $prova['cor']; ?>;
            background: #e3f2fd;
        }

        .alternativa input[type="radio"] {
            margin-right: 12px;
            margin-top: 2px;
        }

        .letra-alternativa {
            font-weight: bold;
            color: <?php echo $prova['cor']; ?>;
            margin-right: 8px;
            min-width: 20px;
        }

        /* Estilos para questões dissertativas */
        .resposta-dissertativa {
            margin-bottom: 30px;
        }

        .resposta-dissertativa label {
            display: block;
            font-weight: 600;
            margin-bottom: 10px;
            color: #333;
        }

        .resposta-dissertativa textarea {
            width: 100%;
            min-height: 120px;
            padding: 15px;
            border: 2px solid #f0f0f0;
            border-radius: 8px;
            font-size: 1rem;
            font-family: inherit;
            resize: vertical;
            transition: border-color 0.2s ease;
        }

        .resposta-dissertativa textarea:focus {
            outline: none;
            border-color: <?php echo $prova['cor']; ?>;
            box-shadow: 0 0 0 3px <?php echo $prova['cor']; ?>20;
        }

        .resposta-dissertativa .dica {
            font-size: 0.9rem;
            color: #666;
            margin-top: 8px;
            font-style: italic;
        }

        .tipo-questao-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .tipo-multipla-escolha {
            background: #e3f2fd;
            color: #1976d2;
        }

        .tipo-dissertativa {
            background: #fff3e0;
            color: #f57c00;
        }

        /* Responsividade para o header */
        @media (max-width: 768px) {
            .questao-header {
                flex-direction: column;
                gap: 15px;
                padding: 15px;
            }

            .questao-assunto {
                position: static;
                transform: none;
                max-width: 100%;
                order: 2;
            }

            .questao-numero {
                order: 1;
            }

            .questao-acoes {
                order: 3;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .questao-assunto {
                font-size: 0.9rem;
                padding: 6px 12px;
            }

            .questao-acoes {
                flex-direction: column;
                width: 100%;
            }

            .btn-voltar, .btn-finalizar {
                width: 100%;
                margin-bottom: 5px;
            }
        }
        
        .navegacao-questao {
            padding: 20px;
            background: #f8f9fa;
            border-top: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .btn-nav {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        
        .btn-anterior {
            background: #6c757d;
            color: white;
        }
        
        .btn-proximo {
            background: <?php echo $prova['cor']; ?>;
            color: white;
        }
        
        .btn-finalizar {
            background: #dc3545;
            color: white;
            padding: 12px 30px;
        }
        
        .btn-nav:hover {
            filter: brightness(1.1);
        }
        
        .btn-nav:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .progresso {
            flex: 1;
            margin: 0 20px;
            text-align: center;
            color: #666;
        }
        
        .barra-progresso {
            width: 100%;
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 5px;
        }
        
        .progresso-fill {
            height: 100%;
            background: <?php echo $prova['cor']; ?>;
            transition: width 0.3s ease;
        }
        
        @media (max-width: 768px) {
            .teste-interface {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                height: auto;
                order: 2;
            }
            
            .navegacao-questoes {
                max-height: 200px;
            }
            
            .area-questao {
                order: 1;
                height: 70vh;
            }
        }
    </style>
</head>
<body>
    <?php include 'header_status.php'; ?>

    <div class="teste-interface">
        <!-- Sidebar com navegação -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2><?php echo $prova['nome']; ?></h2>
                <div class="cronometro" id="cronometro">
                    <span id="tempo-restante"><?php echo gmdate('H:i:s', $tempo_restante); ?></span>
                </div>
            </div>
            
            <div class="navegacao-questoes">
                <h3 style="margin-bottom: 15px; color: #333;">Questões</h3>
                <div class="grid-questoes" id="grid-questoes">
                    <?php for ($i = 1; $i <= $prova['questoes_total']; $i++): ?>
                        <button class="btn-questao <?php echo $i === 1 ? 'atual' : ''; ?>" 
                                onclick="irParaQuestao(<?php echo $i; ?>)" 
                                id="btn-questao-<?php echo $i; ?>">
                            <?php echo $i; ?>
                        </button>
                    <?php endfor; ?>
                </div>
                
                <div class="legenda">
                    <div class="legenda-item">
                        <div class="legenda-cor" style="background: <?php echo $prova['cor']; ?>;"></div>
                        <span>Atual</span>
                    </div>
                    <div class="legenda-item">
                        <div class="legenda-cor" style="background: #4CAF50;"></div>
                        <span>Respondida</span>
                    </div>

                    <div class="legenda-item">
                        <div class="legenda-cor" style="background: white; border: 2px solid #ddd;"></div>
                        <span>Não respondida</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Área principal da questão -->
        <div class="area-questao">
            <div class="questao-header">
                <div class="questao-numero">Questão <span id="numero-questao-atual">1</span></div>
                <div class="questao-assunto" id="assunto-questao">
                    <!-- Assunto será carregado via JavaScript -->
                </div>
                <div class="questao-acoes">
                    <a href="simulador_provas.php" class="btn-voltar">🔙 Voltar para Exames</a>
                    <button class="btn-finalizar" onclick="confirmarFinalizacao()">🏁 Finalizar Teste</button>
                </div>
            </div>
            
            <div class="questao-conteudo">
                <div class="enunciado" id="enunciado-questao">
                    <!-- Conteúdo será carregado via JavaScript -->
                </div>
                
                <div class="alternativas" id="alternativas-questao">
                    <!-- Alternativas serão carregadas via JavaScript -->
                </div>
            </div>
            
            <div class="navegacao-questao">
                <button class="btn-nav btn-anterior" onclick="questaoAnterior()" id="btn-anterior">
                    ← Anterior
                </button>
                
                <div class="progresso">
                    <div>Questão <span id="questao-atual">1</span> de <?php echo $prova['questoes_total']; ?></div>
                    <div class="barra-progresso">
                        <div class="progresso-fill" id="barra-progresso" style="width: <?php echo (1 / $prova['questoes_total']) * 100; ?>%"></div>
                    </div>
                </div>
                
                <button class="btn-nav btn-proximo" onclick="proximaQuestao()" id="btn-proximo">
                    Próxima →
                </button>
            </div>
        </div>
    </div>
    
    <script>
        // Dados do teste
        const questoes = <?php echo json_encode($questoes_simuladas); ?>;
        const tempoRestante = <?php echo $tempo_restante; ?>;
        const sessaoId = <?php echo $sessao_id; ?>;
        const totalQuestoes = <?php echo $prova['questoes_total']; ?>;
        
        let questaoAtual = 1;
        let respostas = {};
        let cronometroInterval;
        
        // Inicializar teste
        document.addEventListener('DOMContentLoaded', function() {
            iniciarCronometro();
            carregarQuestao(1);
            
            // Prevenir fechamento acidental
            window.addEventListener('beforeunload', function(e) {
                e.preventDefault();
                e.returnValue = 'Tem certeza que deseja sair? Seu progresso será perdido.';
            });
        });
        
        function iniciarCronometro() {
            let segundosRestantes = tempoRestante;
            
            cronometroInterval = setInterval(function() {
                if (segundosRestantes <= 0) {
                    clearInterval(cronometroInterval);
                    finalizarTestePorTempo();
                    return;
                }
                
                const horas = Math.floor(segundosRestantes / 3600);
                const minutos = Math.floor((segundosRestantes % 3600) / 60);
                const segundos = segundosRestantes % 60;
                
                document.getElementById('tempo-restante').textContent = 
                    String(horas).padStart(2, '0') + ':' + 
                    String(minutos).padStart(2, '0') + ':' + 
                    String(segundos).padStart(2, '0');
                
                // Alerta quando restam 5 minutos
                if (segundosRestantes === 300) {
                    alert('⚠️ Atenção: Restam apenas 5 minutos!');
                }
                
                segundosRestantes--;
            }, 1000);
        }
        
        function carregarQuestao(numero) {
            const questao = questoes[numero - 1];

            document.getElementById('numero-questao-atual').textContent = numero;
            document.getElementById('questao-atual').textContent = numero;
            document.getElementById('enunciado-questao').innerHTML = questao.enunciado;

            // Atualizar assunto da questão
            const assuntoElement = document.getElementById('assunto-questao');
            if (questao.assunto) {
                assuntoElement.textContent = questao.assunto;
                assuntoElement.style.display = 'block';
            } else if (questao.materia) {
                assuntoElement.textContent = questao.materia;
                assuntoElement.style.display = 'block';
            } else {
                assuntoElement.textContent = 'Questão Geral';
                assuntoElement.style.display = 'block';
            }

            // Carregar alternativas ou campo dissertativo
            const alternativasContainer = document.getElementById('alternativas-questao');
            alternativasContainer.innerHTML = '';

            // Adicionar badge do tipo de questão
            const tipoBadge = document.createElement('div');
            tipoBadge.className = `tipo-questao-badge ${questao.tipo_questao === 'dissertativa' ? 'tipo-dissertativa' : 'tipo-multipla-escolha'}`;
            tipoBadge.textContent = questao.tipo_questao === 'dissertativa' ? '✏️ Questão Dissertativa' : '🔘 Múltipla Escolha';
            alternativasContainer.appendChild(tipoBadge);

            if (questao.tipo_questao === 'dissertativa') {
                // Criar campo de resposta dissertativa
                const respostaDiv = document.createElement('div');
                respostaDiv.className = 'resposta-dissertativa';

                respostaDiv.innerHTML = `
                    <label for="resposta-texto-${numero}">Digite sua resposta:</label>
                    <textarea
                        id="resposta-texto-${numero}"
                        name="resposta-texto"
                        placeholder="Digite sua resposta aqui..."
                        maxlength="1000"
                    >${respostas[numero] || ''}</textarea>
                    <div class="dica">💡 Dica: Seja claro e objetivo em sua resposta. Máximo de 1000 caracteres.</div>
                `;

                alternativasContainer.appendChild(respostaDiv);

                // Adicionar evento de mudança no textarea
                const textarea = respostaDiv.querySelector('textarea');
                textarea.addEventListener('input', function() {
                    const resposta = this.value.trim();
                    if (resposta) {
                        respostas[numero] = resposta;
                        atualizarStatusQuestao(numero);
                        salvarResposta(numero, resposta, 'dissertativa');
                    } else {
                        delete respostas[numero];
                        atualizarStatusQuestao(numero);
                    }
                });

            } else {
                // Questão de múltipla escolha (comportamento original)
                Object.entries(questao.alternativas).forEach(([letra, texto]) => {
                    const div = document.createElement('div');
                    div.className = 'alternativa';
                    if (respostas[numero] === letra) {
                        div.classList.add('selecionada');
                    }

                    div.innerHTML = `
                        <input type="radio" name="resposta" value="${letra}" ${respostas[numero] === letra ? 'checked' : ''}>
                        <span class="letra-alternativa">${letra.toUpperCase()})</span>
                        <span>${texto}</span>
                    `;

                    div.addEventListener('click', function() {
                        selecionarAlternativa(numero, letra, div);
                    });

                    alternativasContainer.appendChild(div);
                });
            }

            // Atualizar navegação
            atualizarNavegacao();
            atualizarProgresso();
        }
        
        function selecionarAlternativa(numeroQuestao, letra, elemento) {
            // Remover seleção anterior
            document.querySelectorAll('.alternativa').forEach(alt => {
                alt.classList.remove('selecionada');
            });

            // Adicionar nova seleção
            elemento.classList.add('selecionada');
            elemento.querySelector('input').checked = true;

            // Salvar resposta
            respostas[numeroQuestao] = letra;

            // Atualizar status da questão
            atualizarStatusQuestao(numeroQuestao);

            // Salvar no servidor
            salvarResposta(numeroQuestao, letra, 'multipla_escolha');
        }
        
        function atualizarStatusQuestao(numero) {
            const btn = document.getElementById(`btn-questao-${numero}`);
            btn.classList.remove('atual', 'respondida');

            if (numero === questaoAtual) {
                btn.classList.add('atual');
            } else if (respostas[numero]) {
                btn.classList.add('respondida');
            }
        }
        
        function irParaQuestao(numero) {
            // Atualizar questão atual
            document.querySelectorAll('.btn-questao').forEach(btn => {
                btn.classList.remove('atual');
            });
            
            questaoAtual = numero;
            carregarQuestao(numero);
            
            // Atualizar todos os status
            for (let i = 1; i <= totalQuestoes; i++) {
                atualizarStatusQuestao(i);
            }
        }
        
        function questaoAnterior() {
            if (questaoAtual > 1) {
                irParaQuestao(questaoAtual - 1);
            }
        }
        
        function proximaQuestao() {
            if (questaoAtual < totalQuestoes) {
                irParaQuestao(questaoAtual + 1);
            }
        }
        

        
        function atualizarNavegacao() {
            document.getElementById('btn-anterior').disabled = questaoAtual === 1;
            document.getElementById('btn-proximo').disabled = questaoAtual === totalQuestoes;
        }
        
        function atualizarProgresso() {
            const progresso = (questaoAtual / totalQuestoes) * 100;
            document.getElementById('barra-progresso').style.width = progresso + '%';
        }
        
        function salvarResposta(questao, resposta, tipo = 'multipla_escolha') {
            // Salvar resposta no servidor via AJAX
            const formData = new FormData();
            formData.append('sessao_id', sessaoId);
            formData.append('questao_numero', questao);
            formData.append('resposta', resposta);
            formData.append('tipo_resposta', tipo);

            fetch('salvar_resposta.php', {
                method: 'POST',
                body: formData
            }).then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log(`✅ Resposta salva: Questão ${questao} = ${resposta} (${tipo})`);
                } else {
                    console.error('❌ Erro ao salvar resposta:', data.error);
                }
            }).catch(error => {
                console.error('❌ Erro de conexão:', error);
            });
        }
        
        function confirmarFinalizacao() {
            const respondidas = Object.keys(respostas).length;
            const naoRespondidas = totalQuestoes - respondidas;
            
            let mensagem = `Você respondeu ${respondidas} de ${totalQuestoes} questões.`;
            if (naoRespondidas > 0) {
                mensagem += `\n\n${naoRespondidas} questões não foram respondidas.`;
            }
            mensagem += '\n\nDeseja finalizar o teste?';
            
            if (confirm(mensagem)) {
                finalizarTeste();
            }
        }
        
        function finalizarTeste() {
            clearInterval(cronometroInterval);

            // Mostrar loading
            const loadingMsg = document.createElement('div');
            loadingMsg.style.cssText = `
                position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%);
                background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 20px rgba(0,0,0,0.3);
                z-index: 10000; text-align: center; font-size: 1.2rem;
            `;
            loadingMsg.innerHTML = '⏳ Finalizando teste...<br><small>Aguarde, estamos processando suas respostas</small>';
            document.body.appendChild(loadingMsg);

            // Enviar respostas para o servidor
            const formData = new FormData();
            formData.append('sessao_id', sessaoId);
            formData.append('respostas', JSON.stringify(respostas));
            formData.append('finalizar', '1');

            fetch('processar_teste.php', {
                method: 'POST',
                body: formData
            }).then(response => {
                document.body.removeChild(loadingMsg);

                if (response.ok) {
                    return response.json();
                } else {
                    throw new Error('Erro HTTP: ' + response.status);
                }
            }).then(data => {
                if (data.sucesso) {
                    // Mostrar resumo rápido antes de redirecionar
                    const resumo = `✅ Teste finalizado com sucesso!

📊 Resumo:
• Questões respondidas: ${data.questoes_respondidas}
• Acertos: ${data.acertos}
• Pontuação: ${data.pontuacao.toFixed(1)}%

Redirecionando para os resultados...`;

                    alert(resumo);
                    window.location.href = `resultado_teste.php?sessao=${sessaoId}`;
                } else {
                    alert('❌ Erro: ' + (data.erro || 'Erro desconhecido'));
                }
            }).catch(error => {
                document.body.removeChild(loadingMsg);
                console.error('Erro:', error);
                alert('❌ Erro de conexão. Verifique sua internet e tente novamente.');
            });
        }
        
        function finalizarTestePorTempo() {
            alert('⏰ Tempo esgotado! O teste será finalizado automaticamente.');
            finalizarTeste();
        }
    </script>
</body>
</html>