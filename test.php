Manual do Usuário
Sistema de Gestão de Encerramento
Versão 1.0
novembro de 2025

Índice



Introdução
Bem-vindo ao Manual do Usuário do Sistema de Gestão de Encerramento. Este manual foi desenvolvido para auxiliar os usuários na utilização das funcionalidades disponíveis no sistema.
O sistema é composto por três módulos principais que trabalham de forma integrada para facilitar o gerenciamento e análise de encerramentos de correspondentes bancários:
Estatística de Encerramento
Análise de Encerramento
Simulador de Encerramento
Este manual fornece instruções detalhadas sobre como utilizar cada funcionalidade, incluindo capturas de tela e exemplos práticos.

1. Estatística de Encerramento
[PLACEHOLDER - Adicione aqui a descrição e instruções para o módulo de Estatística de Encerramento]
[ESPAÇO RESERVADO PARA IMAGENS]

2. Análise de Encerramento
[PLACEHOLDER - Adicione aqui a descrição e instruções para o módulo de Análise de Encerramento]
[ESPAÇO RESERVADO PARA IMAGENS]

3. Simulador de Encerramento
3.1. Visão Geral
O Simulador de Encerramento é uma ferramenta poderosa que permite aos usuários analisar dados históricos e simular diferentes cenários de encerramento de correspondentes bancários. O simulador oferece visualizações gráficas interativas e a capacidade de comparar múltiplos casos simultaneamente.
Principais funcionalidades:
Análise de dados históricos com visualizações gráficas
Simulação de até 3 cenários simultâneos
Cálculo automático baseado na fórmula: TOTAL = REAL_VALUE - CANCELAMENTO + INAUGURAÇÃO
Salvamento e carregamento de casos para análise futura
Exportação de relatórios em formato PDF
Visualização de indicadores AIGL e Agência
[INSERIR IMAGEM: Tela principal do Simulador de Encerramento]
3.2. Interface do Sistema
A interface do Simulador de Encerramento é dividida em três áreas principais:
3.2.1. Barra Superior
A barra superior contém os controles principais do sistema:
Seletor de Mês: Permite selecionar o mês de referência para análise e simulação
Botão Exportar: Gera um relatório em PDF com todos os dados e gráficos
[INSERIR IMAGEM: Barra superior com seletor de mês e botão exportar]
3.2.2. Painel Esquerdo - Histórico
O painel esquerdo exibe dados históricos de encerramentos, incluindo:
Gráfico de barras com totais mensais e trimestrais
Tabela detalhada com valores de Real, Inauguração, Cancelamento e Total
Comparação com o mesmo período do ano anterior
[INSERIR IMAGEM: Painel de histórico com gráfico e tabela]
3.2.3. Painel Direito - Simulação
O painel direito permite criar e gerenciar cenários de simulação:
Abas para até 3 casos simultâneos
Gráfico de visualização do cenário atual
Campos de entrada para valores de cancelamento
Indicadores de REAL_VALUE, Inauguração, Cancelamento e Total
Indicadores AIGL e Agência
[INSERIR IMAGEM: Painel de simulação com casos e valores]
3.2.4. Barra Lateral - Casos Salvos
A barra lateral direita pode ser expandida ou recolhida e contém a lista de casos salvos anteriormente. Cada caso salvo mostra:
Nome do caso
Mês de referência
Botões para carregar ou excluir o caso
[INSERIR IMAGEM: Barra lateral com casos salvos]

3.3. Utilizando o Simulador
3.3.1. Selecionando o Mês de Referência
Para iniciar uma análise ou simulação, é necessário primeiro selecionar o mês de referência:
Clique no campo 'Selecione o Mês' na barra superior
Escolha o mês desejado da lista (últimos 12 meses disponíveis)
O sistema carregará automaticamente os dados históricos e atualizará a simulação
Observação: Ao mudar o mês de referência, todos os casos ativos serão atualizados com os novos valores base (REAL_VALUE e Inauguração).
[INSERIR IMAGEM: Seletor de mês em destaque]
3.3.2. Analisando Dados Históricos
Após selecionar o mês, o painel de histórico exibirá automaticamente:
Mês anterior: Dados do mês imediatamente anterior ao selecionado
Últimos 4 trimestres: Dados consolidados trimestrais
Mesmo mês do ano anterior: Permite comparação ano a ano
A tabela abaixo do gráfico apresenta os valores detalhados para cada período, incluindo:
Real: Valor total de correspondentes ativos no período
Inauguração: Número de correspondentes inaugurados
Cancelamento: Número de correspondentes encerrados
Total: Resultado final (Real - Cancelamento + Inauguração)
[INSERIR IMAGEM: Gráfico e tabela de histórico com dados]
3.3.3. Criando um Novo Caso de Simulação
Para criar um novo cenário de simulação:
Clique no botão '+ Novo Caso' no painel de simulação
Uma nova aba será criada (máximo de 3 casos simultâneos)
O caso será inicializado com os valores base do mês selecionado
Importante: Se já houver 3 casos ativos, você precisará remover um caso existente antes de criar um novo.
[INSERIR IMAGEM: Botão + Novo Caso e abas de casos]
3.3.4. Inserindo Valores de Cancelamento
Cada caso de simulação permite a entrada de valores de cancelamento por categoria:
Localize a seção 'Valores de Cancelamento' no painel direito
Insira valores numéricos em cada campo de categoria
Os valores são atualizados automaticamente conforme você digita
O total de cancelamento é calculado automaticamente (soma de todas as categorias)
O sistema recalcula instantaneamente o valor total usando a fórmula:
TOTAL = REAL_VALUE - CANCELAMENTO + INAUGURAÇÃO
[INSERIR IMAGEM: Campos de entrada de valores de cancelamento]
3.3.5. Visualizando Indicadores
Os indicadores principais são exibidos em destaque no painel de simulação:
REAL_VALUE: Valor base de correspondentes ativos no mês
Inauguração: Número de correspondentes inaugurados (em verde)
Cancelamento: Total de cancelamentos inseridos (em vermelho)
TOTAL: Resultado final da simulação (em azul)
Abaixo dos indicadores principais, são exibidos dois valores adicionais específicos do mês:
AIGL: Indicador específico calculado para o mês selecionado
Agência: Indicador de agências calculado para o mês selecionado
[INSERIR IMAGEM: Painel de indicadores com valores]
3.3.6. Alternando Entre Casos
Quando há múltiplos casos ativos:
Clique na aba do caso desejado para visualizá-lo
A aba ativa será destacada com uma borda diferente
O gráfico e os valores serão atualizados automaticamente
Cada caso mantém seus valores independentemente
[INSERIR IMAGEM: Abas de casos com uma ativa]
3.3.7. Removendo um Caso
Para remover um caso de simulação:
Clique no 'X' ao lado do nome do caso na aba
O caso será removido imediatamente (não há confirmação)
Se era o caso ativo, o sistema mudará automaticamente para outro caso
Atenção: Não é possível remover o último caso. Sempre deve haver pelo menos um caso ativo.
[INSERIR IMAGEM: Botão X para remover caso]

3.4. Salvando e Carregando Casos
3.4.1. Salvando um Caso
Para salvar um caso de simulação para uso futuro:
Configure o caso com os valores desejados
Digite um nome descritivo no campo 'Nome do caso'
Clique no botão 'Salvar Caso' (verde)
Uma mensagem de confirmação aparecerá
O caso aparecerá na lista de casos salvos na barra lateral
O caso salvo incluirá:
Mês de referência
Valores de REAL_VALUE e Inauguração
Todos os valores de cancelamento inseridos
Total calculado
[INSERIR IMAGEM: Campo de nome e botão Salvar Caso]
3.4.2. Carregando um Caso Salvo
Para carregar um caso previamente salvo:
Abra a barra lateral de casos salvos (se estiver recolhida)
Localize o caso desejado na lista
Clique no botão 'Carregar' (azul) ao lado do caso
O caso será adicionado como uma nova aba no painel de simulação
O mês de referência será automaticamente ajustado
Nota: Se já houver 3 casos ativos, você precisará remover um antes de carregar um novo caso.
[INSERIR IMAGEM: Lista de casos salvos com botões Carregar e Excluir]
3.4.3. Excluindo um Caso Salvo
Para remover permanentemente um caso salvo:
Localize o caso na lista de casos salvos
Clique no botão 'Excluir' (vermelho) ao lado do caso
Confirme a exclusão na mensagem que aparecer
O caso será removido permanentemente da lista
Atenção: Esta ação não pode ser desfeita. O caso será excluído permanentemente do banco de dados.
[INSERIR IMAGEM: Confirmação de exclusão de caso]

3.5. Exportando Relatórios
3.5.1. Gerando um Relatório PDF
O sistema permite exportar todas as informações visualizadas em um relatório PDF completo:
Certifique-se de que o mês correto está selecionado
Configure todos os casos que deseja incluir no relatório
Clique no botão 'Exportar' na barra superior
Aguarde enquanto o sistema gera o PDF (pode levar alguns segundos)
O arquivo será baixado automaticamente para seu computador
O relatório PDF incluirá:
Capa com título e mês de referência
Gráfico de histórico de encerramentos
Tabela detalhada de dados históricos
Gráfico de simulação para cada caso ativo
Resumo de valores para cada caso
Detalhamento de cancelamento por categoria
[INSERIR IMAGEM: Botão Exportar em destaque]
3.5.2. Nome do Arquivo Exportado
O arquivo PDF gerado seguirá o padrão de nomenclatura:
simulador_encerramento_YYYY-MM.pdf
Onde YYYY-MM representa o ano e mês de referência selecionado. Por exemplo:
simulador_encerramento_2025-10.pdf (para outubro de 2025)
simulador_encerramento_2025-03.pdf (para março de 2025)
[INSERIR IMAGEM: Exemplo de arquivo PDF gerado]

3.6. Dicas e Boas Práticas
3.6.1. Nomenclatura de Casos
Ao salvar casos, utilize nomes descritivos que facilitem a identificação futura:
Bom: 'Cenário Conservador - Q4 2025'
Bom: 'Alta Taxa Cancelamento - Tipo A'
Ruim: 'Teste 1'
Ruim: 'Caso Novo'
3.6.2. Comparando Cenários
Para comparar diferentes cenários efetivamente:
Crie múltiplos casos com variações específicas
Mantenha o mesmo mês de referência para todos os casos
Alterne entre as abas para comparar visualmente os resultados
Exporte o relatório PDF para documentar as comparações
3.6.3. Análise de Tendências
Utilize os dados históricos para identificar padrões:
Compare trimestres para identificar sazonalidade
Analise o mesmo período do ano anterior para identificar crescimento
Observe as variações entre Real, Inauguração e Cancelamento
Use essas informações para projeções mais precisas
3.6.4. Organização de Casos Salvos
Mantenha sua biblioteca de casos organizada:
Exclua casos antigos ou não mais relevantes
Use prefixos nos nomes para agrupar casos relacionados
Documente casos importantes exportando-os em PDF
Revise periodicamente os casos salvos para manter apenas os úteis
3.6.5. Interpretação dos Indicadores AIGL e Agência
Os indicadores AIGL e Agência fornecem informações complementares específicas do mês:
Estes valores são calculados automaticamente baseados no mês selecionado
Utilize-os como referência adicional ao analisar cenários
Valores diferentes entre meses podem indicar variações sazonais
Consulte a documentação técnica para detalhes sobre os cálculos

3.7. Resolução de Problemas
3.7.1. Dados Não Carregam
Problema: Ao selecionar o mês, os dados históricos ou de simulação não são carregados.
Soluções:
Verifique sua conexão com a internet
Tente recarregar a página (F5)
Selecione um mês diferente e depois retorne ao mês desejado
Limpe o cache do navegador
Entre em contato com o suporte técnico se o problema persistir
3.7.2. Não Consigo Criar Novo Caso
Problema: O botão '+ Novo Caso' não funciona ou exibe uma mensagem de aviso.
Solução:
Verifique se já há 3 casos ativos (limite máximo)
Remova um caso existente clicando no 'X' em uma das abas
Certifique-se de que um mês foi selecionado
3.7.3. Caso Salvo Não Carrega Corretamente
Problema: Ao carregar um caso salvo, os valores não aparecem ou estão incorretos.
Soluções:
Verifique se há espaço para um novo caso (máximo 3)
Remova um caso ativo antes de carregar o caso salvo
Aguarde alguns segundos para o carregamento completo
Se o problema persistir, exclua e crie um novo caso com os mesmos valores
3.7.4. Exportação PDF Falha
Problema: O botão Exportar não gera o arquivo PDF ou ocorre um erro.
Soluções:
Aguarde completamente o carregamento dos gráficos antes de exportar
Verifique se o bloqueador de pop-ups está desabilitado
Certifique-se de que há casos ativos para exportar
Tente usar um navegador diferente
Entre em contato com o suporte se o erro persistir
3.7.5. Valores Não Atualizam
Problema: Ao inserir valores de cancelamento, o total não é recalculado automaticamente.
Soluções:
Pressione Enter após inserir o valor
Clique fora do campo de entrada
Recarregue a página e tente novamente
Limpe o cache do navegador

Glossário
AIGL: Indicador específico calculado mensalmente para análise de correspondentes.
Agência: Indicador de agências calculado mensalmente para contextualização dos dados.
Cancelamento: Número de correspondentes bancários que foram encerrados em um determinado período. Representa a quantidade de pontos de atendimento que deixaram de operar.
Caso: Um cenário de simulação específico com valores configurados pelo usuário. Cada caso representa uma projeção diferente de cancelamentos.
Correspondente Bancário: Estabelecimento credenciado por instituição financeira para prestar serviços bancários de forma terceirizada.
Inauguração: Número de novos correspondentes bancários que iniciaram operações em um determinado período. Representa expansão da rede de atendimento.
REAL_VALUE: Valor base que representa o número total de correspondentes ativos no início do período de referência.
Simulação: Processo de projeção de cenários futuros através da manipulação de variáveis de cancelamento.
Total: Resultado final calculado pela fórmula: TOTAL = REAL_VALUE - CANCELAMENTO + INAUGURAÇÃO. Representa a projeção final de correspondentes ativos.

Informações de Suporte
Para assistência técnica, dúvidas ou sugestões sobre o Sistema de Gestão de Encerramento, entre em contato com a equipe de suporte:
Equipe de Desenvolvimento
Email: [INSERIR EMAIL DE SUPORTE]
Ramal: [INSERIR RAMAL]
Horário de Atendimento: Segunda a Sexta, 8h às 18h
Observações Importantes:
Mantenha o navegador atualizado para melhor experiência
Navegadores recomendados: Chrome, Firefox, Edge (versões recentes)
Certifique-se de ter permissões adequadas para acessar o sistema
Reporte bugs ou comportamentos inesperados imediatamente
Versão do Manual: 1.0
Última Atualização: 14/11/2025
