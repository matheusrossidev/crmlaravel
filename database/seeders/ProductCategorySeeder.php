<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ProductCategory;
use Illuminate\Database\Seeder;

class ProductCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Cortinas e Persianas' => ['Cortina Tradicional','Cortina Romana','Cortina Wave','Cortina Voal','Blackout','Persiana Horizontal','Persiana Vertical','Persiana Rolo','Painel','Bandô','Trilho','Varão','Acessórios para Cortinas'],
            'Decoração e Acabamento' => ['Papel de Parede','Piso Laminado','Piso Vinílico','Porcelanato','Tapete','Almofada','Quadro Decorativo','Espelho','Luminária','Rodapé','Moldura','Revestimento','Adesivo Decorativo'],
            'Marketing e Publicidade' => ['Criação de Site','Landing Page','Social Media','Tráfego Pago (Meta)','Tráfego Pago (Google)','SEO','Email Marketing','Design Gráfico','Identidade Visual','Branding','Produção de Conteúdo','Gestão de Redes','Fotografia Publicitária','Vídeo Marketing','Copywriting'],
            'Tecnologia e Software' => ['Desenvolvimento de Software','Desenvolvimento Web','Desenvolvimento Mobile','SaaS / Licença','Suporte Técnico','Hospedagem','Domínio','Consultoria em TI','Automação','Integração de Sistemas','Segurança da Informação','Cloud Computing','Backup','ERP','CRM'],
            'Saúde' => ['Consulta Médica','Consulta Odontológica','Exame Laboratorial','Exame de Imagem','Cirurgia','Fisioterapia','Psicologia','Nutrição','Fonoaudiologia','Plano de Saúde','Home Care','Vacina','Farmácia / Medicamento'],
            'Estética e Beleza' => ['Limpeza de Pele','Peeling','Botox','Preenchimento','Harmonização Facial','Depilação a Laser','Massagem','Drenagem Linfática','Microagulhamento','Tratamento Capilar','Manicure / Pedicure','Maquiagem','Design de Sobrancelha','Extensão de Cílios','Bronzeamento'],
            'Educação' => ['Curso Online','Curso Presencial','Aula Particular','Workshop','Bootcamp','Mentoria','MBA','Pós-Graduação','Idiomas','Reforço Escolar','Material Didático','Certificação','Treinamento Corporativo'],
            'Alimentação e Gastronomia' => ['Refeição','Marmita / Marmitex','Pizza','Hambúrguer','Sushi','Açaí','Doces e Sobremesas','Bolo','Salgados','Bebidas','Café','Cesta Básica','Produto Natural / Orgânico','Suplemento Alimentar','Catering / Buffet'],
            'Imóveis' => ['Venda de Imóvel','Aluguel de Imóvel','Lançamento / Incorporação','Consultoria Imobiliária','Avaliação de Imóvel','Administração de Condomínio','Seguro Residencial','Financiamento Imobiliário','Reforma e Obra','Projeto Arquitetônico','Design de Interiores','Paisagismo'],
            'Automotivo' => ['Revisão','Troca de Óleo','Freios','Suspensão','Motor','Elétrica Automotiva','Funilaria e Pintura','Ar Condicionado Veicular','Pneu','Alinhamento e Balanceamento','Acessórios Auto','Insulfilm','Som Automotivo','Lavagem','Polimento','Blindagem'],
            'Seguros' => ['Seguro Auto','Seguro Moto','Seguro Residencial','Seguro Empresarial','Seguro de Vida','Seguro Viagem','Seguro Saúde','Seguro Odontológico','Seguro Responsabilidade Civil','Previdência Privada','Consórcio Auto','Consórcio Imóvel','Consórcio Moto'],
            'Jurídico e Contabilidade' => ['Consultoria Jurídica','Advocacia Trabalhista','Advocacia Cível','Advocacia Criminal','Advocacia Empresarial','Direito de Família','Direito Imobiliário','Direito do Consumidor','Contabilidade','Abertura de Empresa','Declaração de IR','Planejamento Tributário','Auditoria','Perícia','Recuperação Judicial'],
            'Financeiro' => ['Empréstimo Pessoal','Empréstimo Empresarial','Financiamento','Cartão de Crédito','Conta Digital','Investimentos','Câmbio','Antecipação de Recebíveis','Crédito Consignado','Factoring','Capital de Giro','Assessoria Financeira','Planejamento Financeiro'],
            'Moda e Vestuário' => ['Roupa Feminina','Roupa Masculina','Roupa Infantil','Calçado','Bolsa','Acessório de Moda','Joia','Relógio','Óculos','Roupa Fitness','Lingerie','Moda Praia','Uniforme','Roupa Sob Medida','Costura e Ajuste'],
            'Fitness e Esportes' => ['Plano Academia','Personal Trainer','Avaliação Física','Suplemento Esportivo','Equipamento Fitness','Artes Marciais','Yoga','Pilates','Natação','Cross Training','Nutrição Esportiva','Roupa Esportiva'],
            'Eventos e Festas' => ['Fotografia','Filmagem','Decoração de Evento','Buffet','DJ','Banda / Música','Convite','Lembrancinha','Bolo de Festa','Locação de Espaço','Cerimonial','Iluminação','Sonorização','Aluguel de Mobiliário','Tendas e Coberturas'],
            'Pet e Veterinário' => ['Banho e Tosa','Consulta Veterinária','Vacina Pet','Cirurgia Veterinária','Ração','Acessório Pet','Roupa Pet','Adestramento','Pet Sitter','Hotel Pet','Transporte Pet','Plano de Saúde Pet','Farmácia Veterinária'],
            'Construção e Reforma' => ['Material de Construção','Cimento','Argamassa','Tijolo','Telha','Tinta','Ferramentas','Encanamento','Fiação Elétrica','Esquadria','Vidro','Gesso','Drywall','Portas','Janelas','Fechadura'],
            'Serviços para Casa' => ['Limpeza Residencial','Limpeza Comercial','Dedetização','Jardinagem','Eletricista','Encanador / Hidráulica','Pintor','Pedreiro','Marcenaria','Serralheria','Vidraceiro','Chaveiro','Mudança','Montagem de Móveis','Impermeabilização'],
            'Móveis' => ['Sofá','Mesa','Cadeira','Cama','Guarda-roupa','Cômoda','Estante','Rack / Painel TV','Mesa de Escritório','Cadeira de Escritório','Berço','Beliche','Móvel Planejado','Móvel Sob Medida','Colchão'],
            'Turismo e Viagens' => ['Passagem Aérea','Hotel / Pousada','Pacote de Viagem','Transfer','Passeio / Tour','Aluguel de Carro','Seguro Viagem','Visto / Documentação','Cruzeiro','Ecoturismo','Turismo de Aventura'],
            'Agronegócio' => ['Semente','Fertilizante','Defensivo Agrícola','Ração Animal','Maquinário Agrícola','Implemento','Irrigação','Consultoria Agro','Análise de Solo','Assistência Técnica','Pecuária','Avicultura','Suinocultura'],
            'Energia e Sustentabilidade' => ['Energia Solar','Painel Solar','Inversor','Bateria','Projeto Fotovoltaico','Eficiência Energética','Automação Residencial','Automação Comercial','Gerador','Nobreak','Recarga Veículo Elétrico'],
            'Logística e Transporte' => ['Frete','Entrega Expressa','Mudança','Armazenagem','Motoboy','Transporte Executivo','Locação de Veículo','Guincho','Rastreamento','Gestão de Frota'],
            'Comunicação e Mídia' => ['Assessoria de Imprensa','Relações Públicas','Podcast','Produção de Vídeo','Streaming','Rádio','TV','Jornal','Revista','Influencer Marketing'],
            'Recursos Humanos' => ['Recrutamento e Seleção','Headhunter','Treinamento','Coaching','Team Building','Folha de Pagamento','Benefícios','Medicina do Trabalho','Segurança do Trabalho','Consultoria de RH'],
            'Gráfica e Impressão' => ['Cartão de Visita','Flyer / Panfleto','Banner','Adesivo','Camiseta Personalizada','Caneca','Placa','Fachada','Embalagem','Rótulo','Cardápio','Convite Impresso','Carimbo'],
            'Eletrônicos e Informática' => ['Computador','Notebook','Celular','Tablet','Impressora','Monitor','Periférico','Peça para PC','Câmera','Drone','Smart Home','Console / Games','Assistência Técnica Eletrônica'],
            'Industrial' => ['Peça Industrial','Usinagem','Soldagem','Caldeiraria','Manutenção Industrial','Automação Industrial','Instrumentação','Compressor','Válvula','Bomba','Motor Industrial','Painel Elétrico'],
            'Religioso e Espiritual' => ['Artigo Religioso','Livro Religioso','Evento Religioso','Retiro','Curso Teológico','Música Gospel','Consultoria Pastoral'],
            'Infantil' => ['Brinquedo','Roupa Infantil','Calçado Infantil','Festa Infantil','Recreação','Berçário','Escola Infantil','Material Escolar','Enxoval de Bebê'],
            'Papelaria e Escritório' => ['Material de Escritório','Papel','Caneta','Agenda','Caderno','Arquivo','Organizador','Etiqueta','Envelope','Pasta'],
            'Cosméticos e Perfumaria' => ['Perfume','Creme Facial','Creme Corporal','Protetor Solar','Shampoo','Condicionador','Maquiagem','Esmalte','Sabonete','Desodorante','Óleo Essencial'],
            'Limpeza e Higiene' => ['Produto de Limpeza','Detergente','Desinfetante','Água Sanitária','Papel Higiênico','Sabão em Pó','Amaciante','Álcool','Luva','Esponja'],
            'Telecomunicações' => ['Plano de Celular','Internet Banda Larga','Fibra Óptica','TV por Assinatura','VoIP','Telefonia Fixa','Rádio Comunicação','PABX'],
        ];

        $order = 0;
        foreach ($categories as $parentName => $children) {
            $parent = ProductCategory::firstOrCreate(
                ['name' => $parentName, 'parent_id' => null],
                ['sort_order' => $order++]
            );

            $childOrder = 0;
            foreach ($children as $childName) {
                ProductCategory::firstOrCreate(
                    ['name' => $childName, 'parent_id' => $parent->id],
                    ['sort_order' => $childOrder++]
                );
            }
        }
    }
}
