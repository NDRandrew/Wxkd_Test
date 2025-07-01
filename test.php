function extractTXTFromXML(xmlDoc) {
    console.log('=== extractTXTFromXML DEBUG START ===');
    
    var txtContent = '';
    
    // Debug: verificar estrutura do XML
    var response = xmlDoc.getElementsByTagName('response')[0];
    if (!response) {
        console.error('No response element found in XML');
        return '';
    }
    
    var txtData = xmlDoc.getElementsByTagName('txtData')[0];
    if (!txtData) {
        console.error('No txtData element found in XML');
        return '';
    }
    
    // Debug: verificar informações de debug
    var debugElement = xmlDoc.getElementsByTagName('debug')[0];
    if (debugElement) {
        var totalRecords = getXMLNodeValue(debugElement, 'totalRecords');
        var filter = getXMLNodeValue(debugElement, 'filter');
        var selectedIds = getXMLNodeValue(debugElement, 'selectedIds');
        
        console.log('Debug info from server:');
        console.log('  totalRecords:', totalRecords);
        console.log('  filter:', filter);
        console.log('  selectedIds:', selectedIds);
    }
    
    // Extrair dados das linhas
    var rows = xmlDoc.getElementsByTagName('row');
    console.log('Total rows found in XML:', rows.length);
    
    if (rows.length === 0) {
        console.warn('No rows found in txtData element');
        return '';
    }
    
    for (var i = 0; i < rows.length; i++) {
        var row = rows[i];
        
        var chave = getXMLNodeValue(row, 'chave_loja');
        var empresa = getXMLNodeValue(row, 'cod_empresa');
        var tipo = getXMLNodeValue(row, 'tipo');
        var dataContrato = getXMLNodeValue(row, 'data_contrato');
        var tipoContrato = getXMLNodeValue(row, 'tipo_contrato');
        
        // Debug para primeira linha
        if (i === 0) {
            console.log('First row data:');
            console.log('  chave_loja:', chave);
            console.log('  cod_empresa:', empresa);
            console.log('  tipo:', tipo);
            console.log('  data_contrato:', dataContrato);
            console.log('  tipo_contrato:', tipoContrato);
        }
        
        // Verificar se há dados válidos
        if (!chave && !empresa && !tipo) {
            console.warn('Row', i, 'has no valid data, skipping');
            continue;
        }
        
        // Converter para formato TXT de 117 posições
        var txtLine = formatToTXTLineDebug(chave, empresa, tipo, dataContrato, tipoContrato, i);
        
        if (txtLine && txtLine.length > 0) {
            txtContent += txtLine + '\r\n';
        } else {
            console.warn('Row', i, 'generated empty TXT line');
        }
    }
    
    console.log('=== extractTXTFromXML RESULT ===');
    console.log('Total content length:', txtContent.length);
    console.log('Total lines:', txtContent.split('\r\n').length - 1); // -1 porque última linha é vazia
    
    if (txtContent.length > 0) {
        var lines = txtContent.split('\r\n');
        console.log('First line preview:', lines[0]);
        console.log('First line length:', lines[0].length);
        if (lines.length > 1) {
            console.log('Second line preview:', lines[1]);
        }
    }
    
    return txtContent;
}

function formatToTXTLineDebug(chave, empresa, tipo, dataContrato, tipoContrato, rowIndex) {
    console.log('formatToTXTLineDebug - Row', rowIndex, 'input:');
    console.log('  chave:', chave);
    console.log('  empresa:', empresa);
    console.log('  tipo:', tipo);
    console.log('  dataContrato:', dataContrato);
    console.log('  tipoContrato:', tipoContrato);
    
    // Formatar campos para TXT (117 posições)
    
    // CHAVE_LOJA - 10 posições, numérico, zeros à esquerda
    var chaveTXT = padLeft(cleanNumeric(chave), 10, '0');
    console.log('  chaveTXT formatted:', chaveTXT, '(length:', chaveTXT.length + ')');
    
    // COD_EMPRESA - 8 posições, numérico, zeros à esquerda  
    var empresaTXT = padLeft(cleanNumeric(empresa), 8, '0');
    console.log('  empresaTXT formatted:', empresaTXT, '(length:', empresaTXT.length + ')');
    
    // TIPO - 20 posições, texto, espaços à direita
    var tipoTXT = padRight(cleanText(tipo), 20, ' ');
    console.log('  tipoTXT formatted:', "'" + tipoTXT + "'", '(length:', tipoTXT.length + ')');
    
    // DATA_CONTRATO - 10 posições, formato YYYY-MM-DD
    var dataTXT = formatDateDebug(dataContrato, 10);
    console.log('  dataTXT formatted:', dataTXT, '(length:', dataTXT.length + ')');
    
    // TIPO_CONTRATO - 25 posições, texto, espaços à direita
    var tipoContratoTXT = padRight(cleanText(tipoContrato), 25, ' ');
    console.log('  tipoContratoTXT formatted:', "'" + tipoContratoTXT + "'", '(length:', tipoContratoTXT.length + ')');
    
    // STATUS - 10 posições, texto, espaços à direita
    var statusTXT = padRight('ATIVO', 10, ' ');
    console.log('  statusTXT formatted:', "'" + statusTXT + "'", '(length:', statusTXT.length + ')');
    
    // OBSERVACOES - 34 posições, texto, espaços à direita
    var obsTXT = padRight('CONTRATO APROVADO', 34, ' ');
    console.log('  obsTXT formatted:', "'" + obsTXT + "'", '(length:', obsTXT.length + ')');
    
    // Juntar tudo (total: 117 posições)
    var linha = chaveTXT + empresaTXT + tipoTXT + dataTXT + tipoContratoTXT + statusTXT + obsTXT;
    
    console.log('  linha before padding:', linha.length, 'chars');
    
    // Garantir exatamente 117 caracteres
    if (linha.length > 117) {
        linha = linha.substring(0, 117);
        console.log('  linha truncated to 117 chars');
    } else if (linha.length < 117) {
        linha = padRight(linha, 117, ' ');
        console.log('  linha padded to 117 chars');
    }
    
    console.log('  final linha length:', linha.length);
    console.log('  final linha preview:', linha.substring(0, 50) + '...');
    
    return linha;
}

function formatDateDebug(dateValue, length) {
    console.log('formatDateDebug input:', dateValue);
    
    if (!dateValue) {
        var result = padLeft('', length, '0');
        console.log('formatDateDebug empty input, returning:', result);
        return result;
    }
    
    // Tentar extrair ano
    var year = '';
    var match = dateValue.match(/(\d{4})/);
    if (match) {
        year = match[1];
        var result = year + '-01-01';
        console.log('formatDateDebug found year:', year, 'returning:', result);
        return result;
    }
    
    var result = padLeft('', length, '0');
    console.log('formatDateDebug no year found, returning:', result);
    return result;
}