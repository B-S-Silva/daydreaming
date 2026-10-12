<?php
require_once 'config.php';

echo "🇧🇷 TESTE DE CONFIGURAÇÃO DE IDIOMA\n";
echo "====================================\n\n";

echo "📍 Configurações atuais:\n";
echo "- Idioma padrão: " . DEFAULT_LANGUAGE . "\n";
echo "- Timezone: " . DEFAULT_TIMEZONE . "\n";
echo "- Locale atual: " . setlocale(LC_ALL, 0) . "\n";
echo "- Data/hora: " . date('d/m/Y H:i:s') . "\n\n";

// Testar mensagens de erro em português
echo "🧪 Testando mensagens do sistema:\n";
echo "=================================\n";

// Simular algumas mensagens que apareceriam no sistema
$mensagens = [
    'sucesso' => '✅ Operação realizada com sucesso!',
    'erro' => '❌ Erro: Não foi possível conectar ao banco de dados',
    'aviso' => '⚠️ Aviso: Alguns dados podem estar desatualizados',
    'info' => 'ℹ️ Informação: Sistema configurado corretamente',
    'login_sucesso' => '🔐 Login realizado com sucesso!',
    'login_erro' => '🚫 Erro: Usuário ou senha incorretos',
    'dados_salvos' => '💾 Dados salvos com sucesso!',
    'arquivo_carregado' => '📁 Arquivo carregado com sucesso!'
];

foreach ($mensagens as $tipo => $mensagem) {
    echo "$mensagem\n";
}

echo "\n📊 Formatação de números e datas:\n";
echo "=================================\n";
echo "Número: " . number_format(1234567.89, 2, ',', '.') . "\n";
echo "Moeda: R$ " . number_format(1234.56, 2, ',', '.') . "\n";
echo "Data completa: " . strftime('%A, %d de %B de %Y às %H:%M') . "\n";
echo "Data curta: " . date('d/m/Y') . "\n";

echo "\n🎯 Teste de conexão com banco (em português):\n";
echo "=============================================\n";

try {
    $conn = conectarBD();
    echo "✅ Conexão com banco de dados estabelecida com sucesso!\n";
    echo "📊 Banco: " . DB_NAME . "\n";
    echo "🖥️ Host: " . DB_HOST . "\n";
    
    // Testar uma consulta simples
    $stmt = $conn->query("SELECT COUNT(*) as total FROM usuarios");
    $result = $stmt->fetch();
    echo "👥 Total de usuários cadastrados: " . $result['total'] . "\n";
    
} catch (Exception $e) {
    echo "❌ Erro na conexão: " . $e->getMessage() . "\n";
}

echo "\n✅ Teste de idioma concluído!\n";
echo "Todas as saídas estão configuradas para português brasileiro.\n";
?>
