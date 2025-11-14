// API serverless para verificar pagamentos (compatível com Vercel)
export default function handler(req, res) {
  // Configurar CORS
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Origin, X-Requested-With, Content-Type, Accept, Authorization');
  
  // Responder às solicitações OPTIONS sem processar
  if (req.method === 'OPTIONS') {
    return res.status(200).end();
  }
  
  // Aceita tanto GET quanto POST
  if (req.method !== 'GET' && req.method !== 'POST') {
    res.setHeader('Allow', ['GET', 'POST']);
    return res.status(405).end('Method Not Allowed');
  }
  
  // Obter ID da transação e número da tentativa
  const { id, attempt } = req.query;
  const attemptNum = parseInt(attempt) || 1;
  
  if (!id) {
    return res.status(400).json({
      success: false,
      status: 'error',
      message: "ID da transação não fornecido"
    });
  }
  
  // Log para depuração
  console.log(`[API Verificar] Verificando transação ${id} (tentativa ${attemptNum})`);
  
  try {
    // Simular um status baseado na tentativa
    // Para testes rápidos, pagamento aprovado após 5 tentativas
    let status;
    
    if (attemptNum >= 5) {
      status = 'paid';
    } else {
      status = 'pending';
    }
    
    const response = {
      success: true,
      status: status,
      transaction_id: id,
      updated_at: new Date().toISOString()
    };
    
    console.log(`[API Verificar] Status da transação ${id}: ${status}`);
    return res.status(200).json(response);
    
  } catch (error) {
    console.error(`[API Verificar] Erro:`, error);
    return res.status(500).json({
      success: false,
      status: 'error',
      message: `Erro ao verificar status: ${error.message}`
    });
  }
} 