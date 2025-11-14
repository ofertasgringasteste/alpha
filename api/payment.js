// API serverless para processar pagamentos em Node.js (compatível com Vercel)
const fetch = require('node-fetch');
const { URLSearchParams } = require('url');

module.exports = async (req, res) => {
  // Configurar CORS
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Origin, X-Requested-With, Content-Type, Accept, Authorization');
  
  // Responder às solicitações OPTIONS sem processar
  if (req.method === 'OPTIONS') {
    return res.status(200).end();
  }
  
  // Log para depuração
  console.log(`[API] Recebendo solicitação ${req.method}`);
  
  try {
    // Dados do pagamento
    const data = req.body;
    console.log(`[API] Dados recebidos:`, data);
    
    // Dados simulados de PIX para resposta imediata (sem depender da API PHP)
    const pixData = {
      success: true,
      qrCodeUrl: "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=00020126580014BR.GOV.BCB.PIX0136a629534c-7df9-4e4b-9c15-9af3040720520204123.455802BR5904NOME6006CIDADE62070503***63041234",
      pixCode: "00020126580014BR.GOV.BCB.PIX0136a629534c-7df9-4e4b-9c15-9af3040720520204123.455802BR5904NOME6006CIDADE62070503***63041234",
      transactionId: `pix_${Date.now()}`,
      expiresAt: new Date(Date.now() + 30*60000).toISOString(), // 30 minutos
      message: "PIX gerado com sucesso"
    };
    
    // Responder com os dados do PIX
    return res.status(200).json(pixData);
    
  } catch (error) {
    console.error(`[API] Erro:`, error);
    return res.status(500).json({
      success: false,
      message: `Erro ao processar pagamento: ${error.message}`
    });
  }
}; 