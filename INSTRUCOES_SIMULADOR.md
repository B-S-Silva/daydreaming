# Sistema de Simulador de Provas - Instruções

## Visão Geral

O Sistema de Simulador de Provas foi criado para permitir que os usuários pratiquem diferentes tipos de exames (ENEM, Vestibular, Concurso, Técnico) com um sistema completo de gamificação, incluindo badges, níveis e acompanhamento de progresso.

## Estrutura do Sistema

### Arquivos Principais

1. **simulador_provas.php** - Página principal de seleção de provas
2. **executar_teste.php** - Gerencia a execução dos testes
3. **interface_teste.php** - Interface do usuário durante o teste
4. **processar_teste.php** - Processa os resultados dos testes
5. **resultado_teste.php** - Exibe os resultados e badges conquistadas
6. **historico_testes.php** - Histórico e estatísticas do usuário
7. **admin_questoes.php** - Painel administrativo para gerenciar questões

### Arquivos de Sistema

1. **badges_manager.php** - Gerencia badges e sistema de níveis
2. **questoes_manager.php** - Gerencia carregamento de questões
3. **simulador_database.sql** - Script de criação das tabelas

## Configuração Inicial

### 1. Executar Script do Banco de Dados

```sql
-- Execute o arquivo simulador_database.sql no seu MySQL
source simulador_database.sql;
```

Este script criará:
- Tabelas para sessões de teste
- Tabelas para resultados e respostas
- Sistema de badges e níveis
- Tabela de questões
- Badges padrão
- Questões de exemplo

### 2. Acessar Painel Administrativo

1. Faça login como administrador
2. Acesse **Admin Forum** → **Gerenciar Questões**
3. Ou acesse diretamente: `admin_questoes.php`

## Gerenciamento de Questões

### Formato JSON Aceito

```json
{
  "metadata": {
    "tipo_prova": "enem",
    "versao": "1.0",
    "data_criacao": "2024-01-15 10:30:00",
    "total_questoes": 2
  },
  "questoes": [
    {
      "numero_questao": 1,
      "enunciado": "Qual é a capital do Brasil?",
      "alternativa_a": "São Paulo",
      "alternativa_b": "Rio de Janeiro",
      "alternativa_c": "Brasília",
      "alternativa_d": "Belo Horizonte",
      "alternativa_e": "Salvador",
      "resposta_correta": "c",
      "dificuldade": "facil",
      "materia": "Geografia",
      "assunto": "Capitais",
      "explicacao": "Brasília é a capital federal do Brasil desde 1960."
    }
  ]
}
```

### Campos Obrigatórios

- `numero_questao`: Número sequencial da questão
- `enunciado`: Texto da pergunta
- `alternativa_a` até `alternativa_e`: Opções de resposta
- `resposta_correta`: Letra da resposta correta (a, b, c, d, e)

### Campos Opcionais

- `dificuldade`: facil, medio, dificil
- `materia`: Matéria da questão
- `assunto`: Assunto específico
- `explicacao`: Explicação da resposta correta

### Upload de Questões

1. Acesse `admin_questoes.php`
2. Selecione o tipo de prova
3. Faça upload do arquivo JSON ou XML
4. O sistema validará e importará as questões

### Baixar Exemplos

No painel administrativo, você pode baixar arquivos de exemplo para cada tipo de prova:
- Exemplo ENEM (JSON)
- Exemplo Vestibular (JSON)
- Exemplo Concurso (JSON)
- Exemplo Técnico (JSON)

## Sistema de Badges

### Badges Padrão

1. **🎯 Primeiro Passo** - Completou primeiro teste
2. **🥉 Satisfatório** - Pontuação 60-69%
3. **🥈 Bom Desempenho** - Pontuação 70-79%
4. **🥇 Muito Bom** - Pontuação 80-89%
5. **🏆 Excelência** - Pontuação acima de 90%
6. **💯 Perfeccionista** - Obteve 100% em um teste
7. **⚡ Velocista** - Completou teste em tempo recorde
8. **📈 Consistente** - 5 resultados acima de 70%
9. **💪 Dedicado** - 10 resultados acima de 70%
10. **🏃 Maratonista** - Completou 20 testes
11. **🔥 Persistente** - Completou 50 testes
12. **🎓 Especialista ENEM** - 5 testes do ENEM
13. **📚 Especialista Vestibular** - 5 testes de Vestibular
14. **🏛️ Especialista Concurso** - 5 testes de Concurso
15. **⚙️ Especialista Técnico** - 5 testes Técnicos

### Sistema de Níveis

- **Nível inicial**: 1
- **Experiência base**: 20 XP por teste completado
- **Bônus por pontuação**: 5 XP por cada 10% de acerto
- **Bônus por alta pontuação**: 10-30 XP extra
- **Bônus por velocidade**: 15 XP extra
- **Badge conquistada**: 50 XP extra

### Fórmula de Experiência por Nível

```
Experiência necessária = 100 * (1.2 ^ (nível - 1))
```

## Tipos de Prova

### ENEM
- **Duração**: 90 minutos
- **Questões**: 20 (configurável)
- **Foco**: Questões interdisciplinares

### Vestibular
- **Duração**: 60 minutos
- **Questões**: 15 (configurável)
- **Foco**: Questões específicas por matéria

### Concurso
- **Duração**: 60 minutos
- **Questões**: 20 (configurável)
- **Foco**: Questões de conhecimentos gerais e específicos

### Técnico
- **Duração**: 45 minutos
- **Questões**: 15 (configurável)
- **Foco**: Questões técnicas e práticas

## Funcionalidades do Sistema

### Para Usuários

1. **Seleção de Prova**: Escolher tipo de simulado
2. **Execução do Teste**: Interface intuitiva com cronômetro
3. **Resultados Detalhados**: Pontuação, acertos, tempo gasto
4. **Badges e Níveis**: Sistema de gamificação
5. **Histórico Completo**: Acompanhamento de progresso
6. **Estatísticas**: Gráficos e métricas de desempenho

### Para Administradores

1. **Upload de Questões**: Importação via JSON/XML
2. **Validação Automática**: Verificação de estrutura
3. **Estatísticas Gerais**: Visão geral do sistema
4. **Gerenciamento**: Controle total sobre questões

## Segurança

- **Autenticação**: Verificação de login obrigatória
- **CSRF Protection**: Tokens de segurança
- **Validação de Dados**: Sanitização de entradas
- **Rate Limiting**: Controle de tentativas
- **Logs de Auditoria**: Rastreamento de ações

## Manutenção

### Backup Regular

```sql
-- Backup das tabelas do simulador
mysqldump -u usuario -p database_name sessoes_teste resultados_testes respostas_usuario badges usuario_badges questoes niveis_usuario > backup_simulador.sql
```

### Limpeza de Dados

```sql
-- Remover sessões antigas (mais de 30 dias)
DELETE FROM sessoes_teste WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY) AND status = 'expirado';

-- Remover respostas de sessões inexistentes
DELETE FROM respostas_usuario WHERE sessao_id NOT IN (SELECT id FROM sessoes_teste);
```

### Monitoramento

- Verificar logs de erro regularmente
- Monitorar performance das consultas
- Acompanhar uso de espaço em disco
- Verificar integridade dos dados

## Troubleshooting

### Problemas Comuns

1. **Erro ao carregar questões**
   - Verificar formato JSON
   - Validar campos obrigatórios
   - Conferir permissões de arquivo

2. **Badges não sendo concedidas**
   - Verificar logs de erro
   - Conferir integridade das tabelas
   - Validar lógica de concessão

3. **Problemas de performance**
   - Verificar índices do banco
   - Otimizar consultas
   - Limpar dados antigos

### Logs Importantes

- Logs de PHP: `/var/log/php/error.log`
- Logs do Apache: `/var/log/apache2/error.log`
- Logs personalizados: Verificar `error_log()` no código

## Expansões Futuras

### Funcionalidades Planejadas

1. **Relatórios Avançados**: Análises detalhadas de desempenho
2. **Questões Adaptativas**: Dificuldade baseada no desempenho
3. **Simulados Cronometrados**: Diferentes modalidades de tempo
4. **Ranking Global**: Comparação entre usuários
5. **Certificados**: Geração automática de certificados
6. **API REST**: Integração com outros sistemas
7. **Mobile App**: Aplicativo dedicado
8. **IA para Questões**: Geração automática de questões

### Melhorias Técnicas

1. **Cache Redis**: Otimização de performance
2. **CDN**: Distribuição de conteúdo
3. **Microserviços**: Arquitetura escalável
4. **Docker**: Containerização
5. **CI/CD**: Deploy automatizado

## Suporte

Para dúvidas ou problemas:

1. Consulte este documento
2. Verifique os logs de erro
3. Teste com dados de exemplo
4. Contate o desenvolvedor do sistema

---

**Versão**: 1.0  
**Data**: Janeiro 2024  
**Autor**: Sistema DayDreamming