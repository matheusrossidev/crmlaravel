<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Política de Privacidade — Syncro</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            background: #fff;
            color: #1e293b;
            line-height: 1.75;
            font-size: 15px;
        }

        .page-header {
            border-bottom: 1px solid #e2e8f0;
            padding: 18px 24px;
        }

        .page-header-inner {
            max-width: 720px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .page-header a {
            font-size: 15px;
            font-weight: 600;
            color: #1e293b;
            text-decoration: none;
        }

        .page-header span {
            font-size: 13px;
            color: #94a3b8;
        }

        .container {
            max-width: 720px;
            margin: 0 auto;
            padding: 48px 24px 80px;
        }

        .page-title {
            font-size: 28px;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 8px;
            letter-spacing: -.3px;
        }

        .page-subtitle {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 48px;
        }

        h2 {
            font-size: 16px;
            font-weight: 600;
            color: #0f172a;
            margin-top: 40px;
            margin-bottom: 12px;
        }

        p {
            color: #374151;
            margin-bottom: 12px;
        }

        p:last-child {
            margin-bottom: 0;
        }

        ul, ol {
            padding-left: 20px;
            margin-bottom: 12px;
        }

        li {
            color: #374151;
            margin-bottom: 6px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            margin: 16px 0;
        }

        th {
            text-align: left;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .4px;
            color: #64748b;
            padding: 8px 12px;
            border-bottom: 1px solid #e2e8f0;
            background: #f8fafc;
        }

        td {
            padding: 10px 12px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: top;
        }

        tr:last-child td {
            border-bottom: none;
        }

        hr {
            border: none;
            border-top: 1px solid #f1f5f9;
            margin: 0;
        }

        .footer {
            text-align: center;
            padding: 24px;
            font-size: 13px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
        }

        .footer a {
            color: #64748b;
            text-decoration: none;
        }
    </style>
</head>
<body>

<header class="page-header">
    <div class="page-header-inner">
        <a href="{{ route('login') }}"><img src="{{ asset('images/logo.png') }}" alt="Syncro" style="height:28px;"></a>
        <span>Atualizado em fevereiro de 2026</span>
    </div>
</header>

<div class="container">

    <h1 class="page-title">Política de Privacidade</h1>
    <p class="page-subtitle">Esta política descreve como a Syncro coleta, utiliza e protege os dados dos usuários e de seus clientes.</p>

    <h2>1. Quem somos</h2>
    <p>A Syncro é uma plataforma de CRM e marketing digital voltada para empresas e equipes comerciais. Oferecemos ferramentas para gestão de leads, atendimento via WhatsApp e Instagram, funis de vendas e campanhas de marketing.</p>
    <p>Esta política se aplica a todos os usuários cadastrados na plataforma — administradores, gestores e operadores de cada organização.</p>

    <hr>

    <h2>2. Dados que coletamos</h2>
    <p>Coletamos os seguintes tipos de dados para o funcionamento da plataforma:</p>

    <table>
        <thead>
            <tr>
                <th>Categoria</th>
                <th>Dados</th>
                <th>Origem</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Dados de conta</strong></td>
                <td>Nome, e-mail, senha (criptografada), função na organização</td>
                <td>Cadastro na plataforma</td>
            </tr>
            <tr>
                <td><strong>Leads e contatos</strong></td>
                <td>Nome, e-mail, telefone, empresa, cargo, origem, anotações, tags, campos personalizados</td>
                <td>Inserção manual, importação, webhooks, integrações</td>
            </tr>
            <tr>
                <td><strong>Mensagens WhatsApp</strong></td>
                <td>Número de telefone, conteúdo de mensagens, mídia, horário de envio</td>
                <td>Integração WhatsApp Business</td>
            </tr>
            <tr>
                <td><strong>Mensagens Instagram</strong></td>
                <td>IGSID, username, foto de perfil, conteúdo de DMs, mídia</td>
                <td>Integração Meta / Instagram Business</td>
            </tr>
            <tr>
                <td><strong>Campanhas</strong></td>
                <td>Nome, plataforma, métricas de desempenho (cliques, impressões, custo)</td>
                <td>Integração Facebook Ads / Google Ads</td>
            </tr>
            <tr>
                <td><strong>Tokens OAuth</strong></td>
                <td>Tokens de acesso de integrações — armazenados criptografados</td>
                <td>Fluxo OAuth das plataformas parceiras</td>
            </tr>
            <tr>
                <td><strong>Logs do sistema</strong></td>
                <td>Registros de atividade, erros, horários de acesso</td>
                <td>Gerado automaticamente</td>
            </tr>
        </tbody>
    </table>

    <hr>

    <h2>3. Como utilizamos os dados</h2>
    <p>Os dados são utilizados exclusivamente para:</p>
    <ul>
        <li><strong>Gestão de leads e pipeline:</strong> organizar contatos em funis de vendas, registrar etapas e histórico.</li>
        <li><strong>Atendimento via mensagens:</strong> exibir conversas de WhatsApp e Instagram no inbox para que a equipe possa atender e acompanhar clientes.</li>
        <li><strong>Automação com IA:</strong> quando habilitado, o histórico recente de mensagens é processado por um modelo de linguagem para gerar respostas automáticas. Nenhum dado é retido pelo provedor de IA além da requisição.</li>
        <li><strong>Campanhas de marketing:</strong> sincronizar métricas de Facebook Ads e Google Ads para análise no painel.</li>
        <li><strong>Relatórios:</strong> gerar relatórios de desempenho, origem de leads e atividade da equipe.</li>
        <li><strong>Segurança:</strong> detectar acessos não autorizados e manter registros de auditoria.</li>
    </ul>
    <p>Não vendemos, alugamos nem compartilhamos dados de leads ou clientes com terceiros para fins comerciais. Os dados pertencem exclusivamente à organização que os inseriu.</p>

    <hr>

    <h2>4. Integrações de terceiros</h2>
    <p>A Syncro se integra com serviços externos. Cada integração é opcional e controlada pelo administrador da organização:</p>

    <table>
        <thead>
            <tr>
                <th>Serviço</th>
                <th>Dados envolvidos</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>WhatsApp Business</strong></td>
                <td>Mensagens, números de telefone, mídia</td>
            </tr>
            <tr>
                <td><strong>Instagram Business (Meta)</strong></td>
                <td>DMs, IGSID, username, foto de perfil, tokens OAuth</td>
            </tr>
            <tr>
                <td><strong>Facebook Ads (Meta)</strong></td>
                <td>Métricas de campanhas, token OAuth (leitura)</td>
            </tr>
            <tr>
                <td><strong>Google Ads</strong></td>
                <td>Métricas de campanhas, token OAuth (leitura)</td>
            </tr>
            <tr>
                <td><strong>Modelos de IA (LLM)</strong></td>
                <td>Histórico recente de mensagens (somente quando agente de IA habilitado)</td>
            </tr>
        </tbody>
    </table>

    <hr>

    <h2>5. Armazenamento e segurança</h2>
    <ul>
        <li><strong>Criptografia em trânsito:</strong> toda comunicação com a plataforma utiliza HTTPS/TLS.</li>
        <li><strong>Criptografia em repouso:</strong> tokens OAuth e credenciais sensíveis são criptografados com AES-256.</li>
        <li><strong>Isolamento por organização:</strong> dados de cada organização são isolados e inacessíveis por outras.</li>
        <li><strong>Controle de acesso por função:</strong> permissões distintas para administradores, gestores e operadores.</li>
        <li><strong>Logs de auditoria:</strong> ações críticas são registradas com data, hora e usuário responsável.</li>
    </ul>

    <hr>

    <h2>6. Retenção de dados</h2>
    <p>Os dados são mantidos enquanto a conta da organização estiver ativa. Ao encerrar o contrato:</p>
    <ul>
        <li>Os dados podem ser exportados em formato padrão mediante solicitação.</li>
        <li>Após o período de carência, os dados são excluídos permanentemente.</li>
        <li>Logs de segurança podem ser retidos por até 12 meses para fins legais.</li>
    </ul>

    <hr>

    <h2>7. Compartilhamento de dados</h2>
    <p>Não compartilhamos dados pessoais com terceiros, exceto:</p>
    <ul>
        <li><strong>Obrigação legal:</strong> quando exigido por lei ou ordem judicial.</li>
        <li><strong>Infraestrutura:</strong> provedores de servidores e banco de dados, sob contrato de confidencialidade, que atuam como operadores.</li>
        <li><strong>Integrações ativadas pelo cliente:</strong> ao conectar plataformas externas, os dados correspondentes transitam conforme as permissões concedidas pelo administrador.</li>
    </ul>

    <hr>

    <h2>8. Seus direitos (LGPD)</h2>
    <p>Em conformidade com a Lei Geral de Proteção de Dados (Lei nº 13.709/2018), você possui os seguintes direitos:</p>
    <ul>
        <li><strong>Acesso:</strong> confirmar se seus dados são tratados e receber uma cópia.</li>
        <li><strong>Correção:</strong> corrigir dados incompletos, inexatos ou desatualizados.</li>
        <li><strong>Eliminação:</strong> solicitar exclusão de dados desnecessários ou tratados irregularmente.</li>
        <li><strong>Portabilidade:</strong> receber seus dados em formato estruturado para uso em outro serviço.</li>
        <li><strong>Revogação de consentimento:</strong> revogar consentimentos previamente concedidos.</li>
        <li><strong>Informação:</strong> saber com quais entidades seus dados são compartilhados.</li>
    </ul>
    <p>Para exercer seus direitos, entre em contato pelo e-mail indicado na seção de Contato. Respondemos em até 15 dias úteis.</p>

    <hr>

    <h2>9. Cookies</h2>
    <p>A Syncro utiliza apenas cookies técnicos essenciais:</p>
    <ul>
        <li><strong>Cookie de sessão:</strong> mantém o usuário autenticado. Expira com o fechamento do navegador ou inatividade.</li>
        <li><strong>Token CSRF:</strong> proteção contra falsificação de requisições.</li>
    </ul>
    <p>Não utilizamos cookies de rastreamento publicitário nem analytics de terceiros na área autenticada.</p>

    <hr>

    <h2>10. Menores de idade</h2>
    <p>A Syncro é destinada exclusivamente a empresas e profissionais. Não coletamos dados de menores de 18 anos. Caso identificados, serão excluídos imediatamente.</p>

    <hr>

    <h2>11. Alterações nesta política</h2>
    <p>Esta política pode ser atualizada periodicamente. Em alterações substanciais, notificaremos os administradores por e-mail com antecedência mínima de 15 dias.</p>

    <hr>

    <h2>12. Contato</h2>
    <p>Para dúvidas ou solicitações relacionadas aos seus dados, entre em contato com nosso Encarregado de Proteção de Dados (DPO):</p>
    <p>E-mail: <a href="mailto:privacidade@syncro.com.br">privacidade@syncro.com.br</a></p>

</div>

<footer class="footer">
    <p>© {{ date('Y') }} Syncro &nbsp;·&nbsp; <a href="{{ route('login') }}">Acessar plataforma</a></p>
</footer>

</body>
</html>
