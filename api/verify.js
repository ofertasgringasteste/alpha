// API serverless para verificar status do pagamento em Node.js (compatível com Vercel)
module.exports = async (req, res) => {
  // Configurar CORS
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Origin, X-Requested-With, Content-Type, Accept, Authorization');
  
  // Responder às solicitações OPTIONS sem processar
  if (req.method === 'OPTIONS') {
    return res.status(200).end();
  }
  
  // Obter ID da transação da query
  const { id } = req.query;
  if (!id) {
    return res.status(400).json({
      success: false,
      message: "ID da transação não fornecido"
    });
  }
  
  // Log para depuração
  console.log(`[API] Verificando transação ${id}`);
  
  try {
    // Simular um status aleatório para demonstração
    const statusOptions = ['pending', 'pending', 'pending', 'pending', 'paid']; // 20% de chance de ser pago
    const randomStatus = statusOptions[Math.floor(Math.random() * statusOptions.length)];
    
    // Para sempre aprovar após algumas tentativas, descomente a linha abaixo
    // const randomStatus = (id.includes('attempt-5')) ? 'paid' : 'pending';
    
    const response = {
      success: true,
      status: randomStatus,
      transaction_id: id,
      updated_at: new Date().toISOString()
    };
    
    console.log(`[API] Status da transação ${id}: ${randomStatus}`);
    return res.status(200).json(response);
    
  } catch (error) {
    console.error(`[API] Erro:`, error);
    return res.status(500).json({
      success: false,
      status: 'error',
      message: `Erro ao verificar status: ${error.message}`
    });
  }
}; 