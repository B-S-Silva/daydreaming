# Script para configurar PowerShell em Português
Write-Host "CONFIGURANDO POWERSHELL PARA PORTUGUÊS BRASILEIRO" -ForegroundColor Green
Write-Host "=================================================" -ForegroundColor Green

# Configurar cultura para português brasileiro
try {
    [System.Threading.Thread]::CurrentThread.CurrentUICulture = 'pt-BR'
    [System.Threading.Thread]::CurrentThread.CurrentCulture = 'pt-BR'

    Write-Host "Cultura configurada para português brasileiro" -ForegroundColor Green
    Write-Host "Cultura atual: $((Get-Culture).DisplayName)" -ForegroundColor Cyan
    Write-Host "UI Culture: $((Get-UICulture).DisplayName)" -ForegroundColor Cyan
}
catch {
    Write-Host "Erro ao configurar cultura: $($_.Exception.Message)" -ForegroundColor Red
}

# Configurar codificação para UTF-8
try {
    [Console]::OutputEncoding = [System.Text.Encoding]::UTF8
    [Console]::InputEncoding = [System.Text.Encoding]::UTF8
    
    Write-Host "✅ Codificação configurada para UTF-8" -ForegroundColor Green
}
catch {
    Write-Host "❌ Erro ao configurar codificação: $($_.Exception.Message)" -ForegroundColor Red
}

# Testar formatação de data em português
Write-Host "`n📅 TESTE DE FORMATAÇÃO:" -ForegroundColor Yellow
Write-Host "======================" -ForegroundColor Yellow
Write-Host "Data atual: $(Get-Date -Format 'dddd, dd \de MMMM \de yyyy')" -ForegroundColor White
Write-Host "Hora atual: $(Get-Date -Format 'HH:mm:ss')" -ForegroundColor White

# Mostrar informações do sistema
Write-Host "`n💻 INFORMAÇÕES DO SISTEMA:" -ForegroundColor Yellow
Write-Host "==========================" -ForegroundColor Yellow
Write-Host "Versão PowerShell: $($PSVersionTable.PSVersion)" -ForegroundColor White
Write-Host "Sistema Operacional: $($PSVersionTable.OS)" -ForegroundColor White
Write-Host "Plataforma: $($PSVersionTable.Platform)" -ForegroundColor White

Write-Host "`n✅ Configuração concluída!" -ForegroundColor Green
Write-Host "Agora o PowerShell está configurado para português brasileiro." -ForegroundColor White
