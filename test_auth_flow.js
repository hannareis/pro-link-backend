const crypto = require('crypto');

const API_URL = 'http://localhost:8080';

// Gera um email aleatório para evitar colisão de usuário já existente
const testEmail = `teste_${crypto.randomBytes(4).toString('hex')}@prolink.com`;
const testPassword = 'SenhaSegura123';
let sessionId = null;

async function runTests() {
  console.log(`\n=== Iniciando Teste de Fluxo Auth (Front <-> Back) ===\n`);

  // 1. Testa Cadastro
  console.log(`1. Criando novo usuário: ${testEmail}`);
  const formData = new FormData();
  formData.append('name', 'Usuário Teste E2E');
  formData.append('email', testEmail);
  formData.append('password', testPassword);
  formData.append('phone', '92999999999');
  formData.append('profile_type', 'profissional');

  try {
    const regRes = await fetch(`${API_URL}/register`, {
      method: 'POST',
      body: formData // Simulando o FormData do cadastre-se.js
    });
    const regData = await regRes.json();
    if (regRes.status !== 201) throw new Error(`Falha no cadastro: ${JSON.stringify(regData)}`);
    console.log('Cadastro realizado com sucesso!');
  } catch (e) {
    console.error('Erro no cadastro:', e.message);
    return;
  }

  // 2. Testa Login e Coleta de Cookie
  console.log(`\n2. Realizando Login com: ${testEmail}`);
  try {
    const loginRes = await fetch(`${API_URL}/login`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ username: testEmail, password: testPassword })
    });

    // Captura os headers para pegar o cookie PHPSESSID
    const setCookieHeader = loginRes.headers.get('set-cookie');
    if (setCookieHeader) {
      // Extrai o PHPSESSID=...; do header
      sessionId = setCookieHeader.split(';')[0];
    }

    const loginData = await loginRes.json();
    if (loginRes.status !== 200) throw new Error(`Falha no login: ${JSON.stringify(loginData)}`);
    console.log(`Login com sucesso! Bem-vindo, ${loginData.user.nome}.`);
    console.log(`   Cookie interceptado: ${sessionId}`);
  } catch (e) {
    console.error('Erro no login:', e.message);
    return;
  }

  // 3. Testa Rota Protegida (Feed) com Cookie de Sessão
  console.log(`\n3. Tentando acessar rota protegida (/feed) com o cookie da sessão...`);
  try {
    const feedRes = await fetch(`${API_URL}/feed`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        'Cookie': sessionId // Simulando envio de "credentials: include" do browser
      }
    });

    // Se voltar 200, significa que o AuthMiddleware deixou passar
    if (feedRes.status === 200) {
      console.log('Rota protegida acessada com sucesso!');
    } else {
      const feedData = await feedRes.json();
      throw new Error(`Acesso bloqueado: HTTP ${feedRes.status} - ${JSON.stringify(feedData)}`);
    }
  } catch (e) {
    console.error('Erro ao acessar rota protegida:', e.message);
    return;
  }

  // 4. Testa Recuperação de Senha
  console.log(`\n4. Solicitando recuperação de senha para: ${testEmail}`);
  try {
    const recRes = await fetch(`${API_URL}/recover-password`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email: testEmail })
    });
    const recData = await recRes.json();
    if (recRes.status !== 200) throw new Error(`Falha: ${JSON.stringify(recData)}`);
    console.log('Rota de recuperação respondeu com sucesso!');
  } catch (e) {
    console.error('Erro na recuperação de senha:', e.message);
  }

  console.log(`\n=== Todos os testes do fluxo de Auth passaram com sucesso! ===\n`);
}

runTests();
