# Brapci Assistente IA

Aplicação web da Brapci para organizar pessoas, instituições, tarefas, anotações e acesso a aplicativos. Desenvolvida em PHP com CodeIgniter 4, Bootstrap e autenticação externa da Brapci.

## Funcionalidades

### Pessoas

- Listagem paginada, busca por nome ou apelido e coluna de celular.
- Cadastro e edição de nome, apelido, CPF, telefones, e-mails e vínculo institucional.
- Busca de cadastros na base Brapci antes da inclusão.
- Compartilhamento por e-mail com leitura ou edição, expiração opcional e revogação pelo proprietário.
- Cópia de e-mail para a área de transferência e links de WhatsApp.
- Fotografia circular com botão de câmera e upload automático.
- Importação de contatos pelo arquivo `_Documments/contacts.csv`.

Os usuários visualizam apenas pessoas para as quais possuem acesso válido, registrado em `persons_user`.

### Instituições

- Listagem paginada, visualização e edição por ícones.
- Cadastro manual ou preenchimento após selecionar um resultado da API v2 do ROR.
- Nome, sigla, identificador ROR, endereço, cidade, estado, país, coordenadas e ano de fundação.
- Identificador ROR único para evitar duplicação.

As instituições são compartilhadas entre os usuários autenticados e podem ser vinculadas às pessoas.

### Kanban

Quadro pessoal com cartões estilo post-it, botão **+** para inserir e ícone de lápis para editar. Cada cartão possui título, descrição, status e prioridade. O status é alterado pelo formulário de edição.

| Status | Exibição |
| --- | --- |
| To DO | Visível |
| Doing | Visível |
| Check | Visível |
| Close | Oculto; o cartão permanece salvo |

| Prioridade | Cor do cartão |
| --- | --- |
| Sem pressa | Verde |
| Normal | Amarelo |
| Urgente | Rosa/vermelho |

Cada usuário acessa somente seus próprios cartões.

### Outros módulos

- **Dashboard:** aplicativos disponíveis para o usuário.
- **Administração:** cadastro de aplicativos e permissões de acesso.
- **Perfil:** informações do usuário autenticado.
- **Anotações:** registros pessoais com título e conteúdo criptografados.
- **Chat:** envio de mensagens a um serviço externo configurável.
- **Interface:** tema escuro, menu lateral, paginação Bootstrap e mensagens de status no rodapé.

## Tecnologias e requisitos

- PHP **8.2 ou superior**, conforme `composer.json`.
- CodeIgniter 4, incluído em `system/`.
- MySQL com driver `MySQLi` e codificação `utf8mb4`.
- Bootstrap 5.3.8 e Bootstrap Icons 1.13.1, carregados por CDN.
- JavaScript e CSS em `public/assets/`.
- Composer para instalar dependências PHP.

Extensões utilizadas: `intl`, `mbstring`, `mysqli`, `curl`, `openssl`, `fileinfo` e `gd`. Os testes de banco também precisam de `sqlite3`.

## Instalação

1. Instale as dependências na raiz do projeto:

   ```sh
   composer install
   ```

2. Copie `env` para `.env` caso ainda não exista. No PowerShell:

   ```powershell
   Copy-Item env .env
   ```

   Não substitua um `.env` já configurado.

3. Crie o banco MySQL e configure a conexão no `.env`.

4. Gere a chave das anotações na primeira instalação:

   ```sh
   php spark key:generate
   ```

   Preserve a chave e seu backup: as anotações existentes dependem dela para serem lidas.

5. Confira e aplique as migrações:

   ```sh
   php spark migrate:status
   php spark migrate
   ```

6. Configure a raiz do servidor web para **`public/`**. O PHP precisa de escrita em `writable/` e em `public/repository/photo/`, criado durante o primeiro upload.

Para desenvolvimento, pode-se usar `php spark serve`. Ajuste `app.baseURL` ao endereço apresentado pelo comando.

### Exemplo de configuração local

```ini
CI_ENVIRONMENT = development
app.baseURL = 'http://assistentia/'
app.indexPage = 'index.php'

database.default.hostname = localhost
database.default.database = assistentia
database.default.username = usuario_do_banco
database.default.password = sua_senha
database.default.DBDriver = MySQLi
database.default.port = 3306

legacyAuth.endpoint = 'https://cip.brapci.inf.br/api/socials/signin'

# Ajuste os caminhos ao servidor.
legacyAuth.caBundle = 'D:/wamp64/cert/cacert.pem'
ror.caBundle = 'D:/wamp64/cert/cacert.pem'

# Opcionais:
# chat.endpoint = 'https://seu-servico.example/chat'
# admin.allowedUserIds = 'id1,id2'
```

Em produção, use `CI_ENVIRONMENT = production` e a URL pública, incluindo o subdiretório da instalação quando houver, por exemplo `https://cip.brapci.inf.br/sudo/`.

Não versione credenciais nem a chave de criptografia. `admin.allowedUserIds` aceita IDs separados por vírgula; a aplicação também reconhece o atributo administrativo do usuário autenticado.

### Certificados HTTPS

O PHP da linha de comando e o PHP do Apache podem carregar arquivos INI diferentes. Configure o arquivo efetivamente usado pelo servidor web:

```ini
curl.cainfo = "D:/wamp64/cert/cacert.pem"
openssl.cafile = "D:/wamp64/cert/cacert.pem"
```

Após modificar o INI, reinicie o Apache pelo painel do Wamp. Ajuste o caminho conforme o ambiente.

- `legacyAuth.caBundle` define o arquivo de certificados do login; quando omitido, usa `curl.cainfo`.
- `ror.caBundle` permite definir o arquivo diretamente para as consultas ao ROR.
- A validação HTTPS permanece ativa.

O erro `SSL certificate problem: unable to get local issuer certificate` indica falha na validação da cadeia de confiança. Confira o arquivo CA, sua leitura pelo PHP e a configuração do servidor web.

## Rotas principais

As rotas são relativas à URL base. Com `app.indexPage = 'index.php'`, `/person` corresponde a `/index.php/person`.

| Método | Rota | Finalidade |
| --- | --- | --- |
| GET | `/` | Página inicial/login |
| POST | `/signin`, `/logout` | Autenticação e saída |
| GET | `/profile` | Perfil |
| GET | `/dashboard` | Aplicativos do usuário |
| GET | `/dashboard/admin` | Administração |
| GET | `/person` | Listagem e busca de pessoas |
| GET | `/person/new` | Busca e novo cadastro |
| POST | `/person` | Salvar pessoa |
| GET | `/person/{id}` | Visualizar pessoa |
| GET | `/person/{id}/edit` | Formulário de edição |
| POST | `/person/{id}/update` | Salvar alterações |
| POST | `/person/{id}/photo` | Atualizar fotografia |
| POST | `/person/{id}/share` | Compartilhar acesso |
| POST | `/person/{id}/shares/{grantId}/revoke` | Revogar acesso |
| POST | `/person/import` | Importar contatos |
| GET | `/corporatebody` | Listar instituições |
| GET | `/corporatebody/new` | Cadastro e busca ROR |
| POST | `/corporatebody` | Salvar instituição |
| GET | `/corporatebody/{id}` | Visualizar instituição |
| GET | `/corporatebody/{id}/edit` | Editar instituição |
| POST | `/corporatebody/{id}/update` | Salvar alterações |
| GET | `/kanban` | Quadro pessoal |
| GET | `/kanban/new` | Novo cartão |
| POST | `/kanban` | Salvar cartão |
| GET | `/kanban/{id}/edit` | Editar cartão |
| POST | `/kanban/{id}/update` | Salvar alterações |
| GET | `/notepad` | Anotações pessoais |
| GET | `/chat` | Interface do chat |
| POST | `/chat/messages` | Enviar mensagem |

Os módulos exigem autenticação. Operações POST são protegidas por CSRF; a administração também exige o filtro `admin`. A lista completa está em [app/Config/Routes.php](app/Config/Routes.php).

## Importação de contatos

O botão **Importar** na listagem de pessoas permite enviar um CSV de até 5 MB. O arquivo enviado é processado temporariamente e não é publicado. O arquivo de exemplo fica em `_Documments/contacts.csv` no projeto. O arquivo enviado deve estar no formato de exportação de contatos Google, com cabeçalho, separador vírgula e texto UTF-8.

- Importa nome, apelido, até dois e-mails e dois telefones.
- Vincula os registros ao usuário conectado com acesso permanente de edição.
- Reconhece contatos do usuário pelo nome completo (ignorando maiúsculas e espaços repetidos) ou telefone normalizado, evitando novas duplicatas. Correspondências com mais de um cadastro são sinalizadas sem atribuir uma foto automaticamente.
- Informa totais importados, duplicados e inválidos.
- Usa transação para desfazer as inserções da tentativa em caso de falha de processamento ou gravação.

A coluna Photo importa imagens HTTPS de lh*.googleusercontent.com, convertidas para JPEG com nome MD5 aleatório. Contatos existentes recebem a foto somente quando não possuem uma. Fotos indisponíveis são contabilizadas separadamente, sem desfazer a importação dos contatos; links com falha aguardam cinco minutos antes de nova tentativa. O download é limitado por execução: se houver fotos pendentes, envie o mesmo CSV pelo botão **Importar** novamente para continuar. Fotografias são processadas após a transação dos contatos. Notas e outros campos não mapeados não são importados. O arquivo pode conter dados pessoais e deve permanecer fora da raiz pública.

## Fotografias

São aceitos JPG, PNG e WebP de até **5 MB** e **16 megapixels**. A imagem é convertida para JPEG, limitada a 1200 pixels no maior lado.

Os arquivos são salvos em `public/repository/photo/<md5-gerado-com-dados-aleatorios>.jpg`. O nome não deriva do ID da pessoa. O upload exige permissão de edição.

As fotografias ficam na pasta pública; um nome imprevisível não equivale a controle de acesso ao arquivo.

## Banco de dados e integrações

| Tabela | Uso |
| --- | --- |
| `persons` | Dados pessoais e referência da fotografia |
| `persons_user` | Propriedade e compartilhamento de cadastros |
| `institutions` | Instituições e dados ROR |
| `kanban_items` | Cartões vinculados ao usuário |
| `user_notes` | Anotações criptografadas |

Também existem migrações para registros de login, aplicativos e permissões. Os IDs de usuário vêm da autenticação externa.

A busca de pessoas na Brapci consulta **`brapci.users`** pela conexão MySQL configurada. Essa tabela externa não é criada pelas migrações; o usuário do banco precisa de permissão de leitura para essa funcionalidade.

O chat envia a mensagem e os dados de sessão do usuário ao endereço definido em `chat.endpoint`. Sem essa configuração, o envio retorna serviço não configurado.

## Estrutura

```text
app/
  Config/                 Configurações, rotas e filtros
  Controllers/            Fluxos HTTP
  Database/Migrations/    Evolução do banco
  Filters/                Autenticação e administração
  Libraries/              Integrações, importação e permissões
  Models/                 Acesso e validação de dados
  Views/                  Templates das páginas
public/
  assets/css/             Estilos e tipografia
  assets/js/              Comportamentos da interface
  repository/photo/       Fotografias
system/                   Framework CodeIgniter
tests/                    Testes automatizados
writable/                 Logs, cache e sessões
_Documments/               Documentos e CSV
```

## Testes

Execute na raiz do projeto:

```sh
php tests/person_access.php
php tests/person_lookup.php
php tests/person_import.php
php tests/person_photo.php
php tests/institutions.php
php tests/corporatebody.php
php tests/kanban.php
```

Os testes de dados usam bancos SQLite isolados. O teste de importação também lê `_Documments/contacts.csv`; o teste de fotografia utiliza GD e arquivos temporários.

Para consultar o ROR real, com acesso à internet e certificados configurados:

```sh
php tests/corporatebody.php --live
```

Os testes verificam permissões, isolamento por usuário, validação, importação, fotografias e renderização de componentes. Não substituem a revisão visual no navegador.

## Operação

- Consulte `writable/logs/` para investigar erros.
- Use `php spark routes` para conferir as rotas e `php spark migrate:status` para consultar as migrações.
- O CSS principal recebe versão baseada na alteração do arquivo para evitar estilos antigos em cache.
- Bootstrap e seus ícones dependem de acesso à CDN.
- Faça backup do banco, das fotografias e da chave de criptografia antes de atualizar a instalação.
- Ao publicar alterações com novas migrações, execute `php spark migrate` no ambiente de destino.

## Licença

O repositório inclui a licença MIT em [LICENSE](LICENSE).
