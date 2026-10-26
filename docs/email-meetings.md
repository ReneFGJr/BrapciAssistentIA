# Reuniões a partir de e-mails

Com o Google Agenda configurado, o leitor de e-mail mostra **Agendar reunião**.
O formulário permite escolher título, início e término no horário de Brasília,
local, convidados e criação opcional do Google Meet. Convidados informados
recebem convites do Google; sem convidados, o evento fica somente na agenda.

É necessária a integração particular `usergoogleSchedule`, autorizada por OAuth
com o escopo `https://www.googleapis.com/auth/calendar.events`. API keys e
autorizações antigas de somente leitura continuam permitindo consulta, mas
exigem **Conectar com Google** novamente antes de criar eventos. A mesma
permissão deve estar disponível na configuração de consentimento do projeto
Google Cloud. A criação de Meet depende do suporte da conta/agenda Google.

O evento é criado por `events.insert`, com `conferenceDataVersion=1` para
habilitar Meet. A descrição inclui referência, assunto, remetente, Message-ID e
até 2.000 caracteres do e-mail. O usuário deve revisar os convidados antes de
compartilhar esse conteúdo. O evento contém um link para a mensagem, e o e-mail
passa a mostrar título, horário e links para o evento e Meet, quando disponível.

A migração `2026-10-26-000003_CreateEmailMeetings` cria os vínculos locais.
Execute `php spark migrate` no ambiente em que a aplicação for publicada.
O evento também é inserido no armazenamento local da agenda sem substituir os
outros eventos. A criação é confirmada pela API antes de exibir o vínculo.

Uma tentativa guarda o ID do evento antes da chamada à API. Ao tentar novamente
no mesmo formulário, o sistema consulta esse ID e recupera o evento existente,
inclusive após timeout ou conflito HTTP 409. Os dados originais devem ser
mantidos nessa recuperação. Nenhuma reunião é criada automaticamente ao abrir
o e-mail ou o formulário.

Referência: [Google Calendar events.insert](https://developers.google.com/workspace/calendar/api/v3/reference/events/insert).

Verificações locais, sem eventos ou convites reais:

```text
php tests/email_meetings.php
php tests/private_google_schedule.php
php tests/google_schedule.php
node tests/email_meeting_operations.cjs
```
