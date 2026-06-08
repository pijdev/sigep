$Origem     = "C:\Sites\sigep-hml"
$Destino    = "C:\Sites\sigep"
$DiretorioLog = "$Origem\storage\backup\logs\sync_hml_prd"
$DataAtual  = Get-Date -Format "dd-MM-yyyy_as_HH-mm\h"
$ArquivoLog = "$DiretorioLog\log_$DataAtual.txt"

if (!(Test-Path -Path $DiretorioLog)) {
    New-Item -ItemType Directory -Path $DiretorioLog | Out-Null
}

$DataExtenso = Get-Date -Format "dd/MM/yyyy 'às' HH:mm:ss"
$Cabecalho = @"
======================================================================
 INÍCIO DA SINCRONIZAÇÃO: HML -> PRD
 Data/Hora: $DataExtenso
 Origem:    $Origem
 Destino:   $Destino
======================================================================
"@
$Cabecalho | Out-File -FilePath $ArquivoLog -Encoding utf8

robocopy $Origem $Destino /MIR /R:0 /W:0 /NP /TS >> $ArquivoLog

$DataFimExtenso = Get-Date -Format "dd/MM/yyyy 'às' HH:mm:ss"
$Rodape = @"

======================================================================
 FIM DA SINCRONIZAÇÃO
 Concluído em: $DataFimExtenso
======================================================================
"@
$Rodape | Out-File -FilePath $ArquivoLog -Append -Encoding utf8
