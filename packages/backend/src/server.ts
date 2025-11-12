import express, { Request, Response } from 'express';
import cors, { CorsOptions } from 'cors';
import dotenv from 'dotenv';
import authRoutes from './routes/authRoutes';
import postRoutes from './routes/postRoutes';
import mediaRoutes from './routes/mediaRoutes';
import aiRoutes from './routes/aiRoutes';
import subscriptionRoutes from './routes/subscriptionRoutes';
import cronJobs from './jobs/cron';

dotenv.config();

const app = express();
const port = process.env.PORT || 3001;

// Middlewares
// Configuração do CORS
const allowedOrigins = [
  'http://localhost:3000', // Para desenvolvimento local do Frontend
  'https://simplifikapost.com.br', // Domínio de produção do Frontend
  // Adicione outros domínios de produção/staging aqui
];

const corsOptions: CorsOptions = {
  origin: (origin, callback) => {
    // Permite requisições sem 'origin' (como apps mobile ou curl)
    if (!origin) return callback(null, true);
    if (allowedOrigins.indexOf(origin) !== -1) {
      callback(null, true);
    } else {
      callback(new Error('Not allowed by CORS'), false);
    }
  },
  credentials: true, // Permite cookies e headers de autorização
};

app.use(cors(corsOptions));
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Rotas
app.get('/', (req: Request, res: Response) => {
  res.json({
    message: 'Bem-vindo à API do SimplifikaPost!',
    version: '1.1.0',
    endpoints: {
      auth: '/api/auth',
      posts: '/api/posts',
      media: '/api/media',
      ai: '/api/ai',
      subscriptions: '/api/subscriptions',
    },
  });
});

app.use('/api/auth', authRoutes);
app.use('/api/posts', postRoutes);
app.use('/api/media', mediaRoutes);
app.use('/api/ai', aiRoutes);
app.use('/api/subscriptions', subscriptionRoutes);

// Health check
app.get('/health', (req: Request, res: Response) => {
  res.status(200).json({ status: 'ok', timestamp: new Date().toISOString() });
});

// 404 handler
app.use((req: Request, res: Response) => {
  res.status(404).json({ error: 'Endpoint não encontrado' });
});

// Error handler
app.use((err: any, req: Request, res: Response, next: any) => {
  console.error('Erro não tratado:', err);
  res.status(500).json({ error: 'Erro interno do servidor' });
});

// Inicia o servidor
app.listen(port, () => {
  console.log(`🚀 Servidor rodando na porta ${port}`);
  console.log(`📍 URL: http://localhost:${port}`);
  console.log(`🔧 Ambiente: ${process.env.NODE_ENV || 'development'}`);
  
  // Inicia os cron jobs
  if (process.env.ENABLE_CRON !== 'false') {
    cronJobs.start();
  }
});

export default app;
