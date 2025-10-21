$.ajax({
  url: 'https://10.222.217.237/portal_tds/bacen_verify',
  method: 'POST',
  contentType: 'application/json',
  dataType: 'json',
  data: JSON.stringify({
    user: 'test', pwd: 'test', token: 'test', cnpj: '12345678'
  }),
  success: data => console.log('Works!', data),
  error: (xhr, status, error) => console.log('Error:', error, xhr.responseText)
});