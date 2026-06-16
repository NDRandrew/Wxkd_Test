<div style="font-family: 'Segoe UI', Arial, sans-serif; color: #1a1a1a; line-height: 1.6; max-width: 680px;">

  <!-- Cabeçalho -->
  <div style="border-left: 5px solid #cc092f; padding: 10px 16px; margin-bottom: 20px;">
    <p style="margin: 0; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #888888;">Bradesco · Qualidade de Dados</p>
    <h2 style="margin: 4px 0 0 0; font-size: 18px; color: #cc092f;">📋 Relatório Diário de Chamados</h2>
  </div>

  <!-- Bloco CLOUD -->
  <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 12px; border-collapse: collapse;">
    <tr>
      <td style="background-color: #f5f5f5; border-left: 4px solid #cc092f; padding: 12px 16px;">
        <p style="margin: 0 0 8px 0; font-size: 13px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; color: #555555;">☁️ Ambiente: CLOUD</p>
        <table cellpadding="0" cellspacing="0">
          <tr>
            <td style="padding: 2px 12px 2px 0; font-size: 13px; color: #444444;">Total Abertos</td>
            <td style="padding: 2px 0; font-size: 13px; font-weight: bold; color: #1a1a1a;">@{length(body('Filtro_Cloud'))}</td>
          </tr>
          <tr>
            <td style="padding: 2px 12px 2px 0; font-size: 13px; color: #444444;">Fora do Prazo (&gt; 5 dias)</td>
            <td style="padding: 2px 0; font-size: 13px; font-weight: bold; color: #1a1a1a;">@{length(body('Fora_do_Prazo_Cloud'))}</td>
          </tr>
          <tr>
            <td style="padding: 2px 12px 2px 0; font-size: 13px; color: #444444;">⚠️ Fora do Prazo e EM ANDAMENTO</td>
            <td style="padding: 2px 0; font-size: 14px; font-weight: bold; color: #cc092f;">@{length(body('Atrasados_Em_Andamento_Cloud'))}</td>
          </tr>
        </table>
      </td>
    </tr>
  </table>

  <!-- Bloco HIVE / TERADATA -->
  <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 12px; border-collapse: collapse;">
    <tr>
      <td style="background-color: #f5f5f5; border-left: 4px solid #cc092f; padding: 12px 16px;">
        <p style="margin: 0 0 8px 0; font-size: 13px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; color: #555555;">🗄️ Ambiente: HIVE / TERADATA</p>
        <table cellpadding="0" cellspacing="0">
          <tr>
            <td style="padding: 2px 12px 2px 0; font-size: 13px; color: #444444;">Total Abertos</td>
            <td style="padding: 2px 0; font-size: 13px; font-weight: bold; color: #1a1a1a;">@{length(body('Filtro_Hive_Teradata'))}</td>
          </tr>
          <tr>
            <td style="padding: 2px 12px 2px 0; font-size: 13px; color: #444444;">Fora do Prazo (&gt; 5 dias)</td>
            <td style="padding: 2px 0; font-size: 13px; font-weight: bold; color: #1a1a1a;">@{length(body('Fora_do_Prazo_Hive_Teradata'))}</td>
          </tr>
          <tr>
            <td style="padding: 2px 12px 2px 0; font-size: 13px; color: #444444;">⚠️ Fora do Prazo e EM ANDAMENTO</td>
            <td style="padding: 2px 0; font-size: 14px; font-weight: bold; color: #cc092f;">@{length(body('Atrasados_Em_Andamento_Hive_Teradata'))}</td>
          </tr>
        </table>
      </td>
    </tr>
  </table>

  <!-- Bloco SAS -->
  <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 20px; border-collapse: collapse;">
    <tr>
      <td style="background-color: #f5f5f5; border-left: 4px solid #cc092f; padding: 12px 16px;">
        <p style="margin: 0 0 8px 0; font-size: 13px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; color: #555555;">📊 Ambiente: SAS</p>
        <table cellpadding="0" cellspacing="0">
          <tr>
            <td style="padding: 2px 12px 2px 0; font-size: 13px; color: #444444;">Total Abertos</td>
            <td style="padding: 2px 0; font-size: 13px; font-weight: bold; color: #1a1a1a;">@{length(body('Filtro_SAS'))}</td>
          </tr>
          <tr>
            <td style="padding: 2px 12px 2px 0; font-size: 13px; color: #444444;">Fora do Prazo (&gt; 5 dias)</td>
            <td style="padding: 2px 0; font-size: 13px; font-weight: bold; color: #1a1a1a;">@{length(body('Fora_do_Prazo_SAS'))}</td>
          </tr>
          <tr>
            <td style="padding: 2px 12px 2px 0; font-size: 13px; color: #444444;">⚠️ Fora do Prazo e EM ANDAMENTO</td>
            <td style="padding: 2px 0; font-size: 14px; font-weight: bold; color: #cc092f;">@{length(body('Atrasados_Em_Andamento_SAS'))}</td>
          </tr>
        </table>
      </td>
    </tr>
  </table>

  <!-- Divisor -->
  <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 16px;">
    <tr><td style="border-top: 1px solid #dddddd; font-size: 0;">&nbsp;</td></tr>
  </table>

  <!-- Tabela de Produtos -->
  <p style="margin: 0 0 4px 0; font-size: 14px; font-weight: bold; color: #1a1a1a;">Detalhamento por Produto de Dados</p>
  <p style="margin: 0 0 12px 0; font-size: 12px; color: #777777;">Volumetria cruzada de chamados por produto:</p>

  <div style="border: 1px solid #e0e0e0; border-top: 3px solid #cc092f; padding: 12px; background-color: #ffffff;">
    @{body('Criar_tabela_HTML')}
  </div>

  <!-- Rodapé -->
  <p style="margin: 16px 0 0 0; font-size: 11px; color: #aaaaaa; text-align: center;">
    Gerado automaticamente · Bradesco — BRAI4DQ · Qualidade de Dados
  </p>

</div>
