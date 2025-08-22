# Sistema de Presença com QR Code para Sala de Aula

Uma aplicação web em PHP para gerenciar presença em sala de aula usando QR codes. O sistema possui dois tipos de usuários: professores e alunos.

## Objetivos

- Resolver problemas de segurança que sistemas atuais do mesmo propósito possuem (identificação única de estudante, garantia de presença válida, necessidade do uso de internet);
- Permitir observar os dados das presenças mais facilmente;


## Funcionalidades

### Para Professores
- Sistema de login;
- Painel de controle com lista de turmas;
- Geração de QR code para cada dia de aula;
- QR codes com tempo limitado (válido por 5 minutos);
- Gráficos de dados em relação a presença dos alunos;

### Para Alunos
- Sistema de login;
- Painel com leitor de QR code;
- Registro de presença em tempo real;
- Leitura de QR code através da câmera;
- Identificação de dispositivo (para que uma pessoa não possa registrar para mais de um aluno com o mesmo celular);
- Identificação de localização (para que uma pessoa não possa receber presença se não estiver fisicamente presente);

## Requisitos Técnicos

- PHP 7.4 ou superior;
- PostgreSQL 12 ou superior;
- Navegador moderno com suporte a acesso à câmera;
- Conexão com internet (para recursos CDN);

## Configuração do Banco de Dados

1. Crie um banco de dados PostgreSQL chamado `attendance_system`
2. Ajuste as configurações de conexão em `config/database.php`:
   - host
   - db_name
   - username
   - password
   - port
3. Execute o arquivo `test_db.php` para verificar a conexão

## Instalação

1. Clone ou baixe este repositório para o diretório do seu servidor web
2. Certifique-se que o servidor web tenha as permissões adequadas para ler/escrever arquivos
3. Configure o banco de dados conforme instruções acima
4. Acesse a aplicação através do seu navegador

## Bibliotecas Utilizadas

- QRCode.js (Geração de QR Code)
- HTML5-QRCode (Leitura de QR Code)

## Notas de Segurança 

Este é um projeto de demonstração e inclui medidas básicas de segurança. Para uso em produção, é preciso fazer diversas melhorias:

1. Implementar armazenamento adequado em banco de dados para:
   - Contas de usuários
   - Informações das turmas
   - Registros de presença
2. Implementar proteção CSRF
3. Usar gerenciamento seguro de sessão
4. Adicionar limite de taxa para geração e leitura de QR code
5. Implementar registro adequado de erros

## Estrutura do Projeto

```
/php_student
├── config/
│   └── database.php    # Configuração do banco de dados
├── index.php           # Ponto de entrada, redireciona para o painel
├── login.php           # Página de login para professores e alunos
├── logout.php          # Gerencia destruição da sessão 
├── teacher/
│   └── dashboard.php   # Painel do professor com geração de QR
└── student/
    ├── dashboard.php   # Painel do aluno com leitor de QR
    └── record_attendance.php # Gerencia registro de presença
```
