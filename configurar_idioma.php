<?php
echo "🌍 CONFIGURAÇÃO DE IDIOMA - PORTUGUÊS BRASILEIRO\n";
echo "================================================\n\n";

// Verificar locale atual
echo "📍 Locale atual: " . setlocale(LC_ALL, 0) . "\n";

// Tentar configurar diferentes variações de português brasileiro
$locales_pt = [
    'pt_BR.UTF-8',
    'pt_BR',
    'Portuguese_Brazil.1252',
    'Portuguese_Brazil',
    'Portuguese',
    'ptb'
];

echo "\n🔄 Tentando configurar locale para português...\n";

$sucesso = false;
foreach ($locales_pt as $locale) {
    $resultado = setlocale(LC_ALL, $locale);
    if ($resultado) {
        echo "✅ Sucesso! Locale configurado: $locale -> $resultado\n";
        $sucesso = true;
        break;
    } else {
        echo "❌ Falhou: $locale\n";
    }
}

if (!$sucesso) {
    echo "\n⚠️ Não foi possível configurar locale português.\n";
    echo "💡 Usando configurações padrão do sistema.\n";
}

// Configurar timezone para Brasil
date_default_timezone_set('America/Sao_Paulo');
echo "\n🕐 Timezone configurado: " . date_default_timezone_get() . "\n";
echo "📅 Data/hora atual: " . date('d/m/Y H:i:s') . "\n";

// Verificar configurações do PHP
echo "\n📋 CONFIGURAÇÕES DO PHP:\n";
echo "========================\n";
echo "Versão PHP: " . PHP_VERSION . "\n";
echo "Charset interno: " . ini_get('default_charset') . "\n";
echo "Locale monetário: " . setlocale(LC_MONETARY, 0) . "\n";
echo "Locale numérico: " . setlocale(LC_NUMERIC, 0) . "\n";
echo "Locale de tempo: " . setlocale(LC_TIME, 0) . "\n";

// Testar formatação em português
echo "\n🧪 TESTE DE FORMATAÇÃO:\n";
echo "=======================\n";
echo "Data formatada: " . strftime('%A, %d de %B de %Y') . "\n";
echo "Número formatado: " . number_format(1234.56, 2, ',', '.') . "\n";

echo "\n✅ Configuração de idioma concluída!\n";
?>
