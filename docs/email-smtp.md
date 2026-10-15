Envio pela conta de e-mail
=========================

Responder e encaminhar usam a conta aberta na caixa de entrada: o endereço email_user é o remetente e o usuário SMTP; a senha criptografada pass_user é descriptografada somente no servidor para autenticar o envio. A configuração global ufrgsMail não é mais utilizada.

Em `/email/edit/{id}`, configure o servidor, a porta e a segurança SMTP da conta. Por padrão, o sistema usa o mesmo servidor de recebimento, porta 587 e STARTTLS. Não reutiliza a porta IMAP/POP3. Quando o provedor usa outro servidor para SMTP, preencha esse endereço no campo Servidor SMTP. Para TLS implícito, selecione SSL e a porta fornecida pelo provedor (normalmente 465).

Responder envia ao remetente original. Encaminhar envia exclusivamente ao endereço do perfil autenticado. O conteúdo original é incluído como texto, sem anexos. Nenhuma mensagem é enviada automaticamente ao abrir a caixa de entrada.
