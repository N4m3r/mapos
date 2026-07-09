-- ============================================================
-- Mapos - Atualiza o CSS global dos e-mails para o tema moderno
-- (clean, com degradê de tons de azul).
-- Rode UMA vez no phpMyAdmin/Adminer. Sobrescreve o email_css atual.
-- ============================================================
SET NAMES utf8mb4;

UPDATE `configuracoes`
SET `valor` = 'body { margin: 0; padding: 0; background: #eaf1fb; }
.email-bg { background: #eaf1fb; background: linear-gradient(180deg, #eaf1fb 0%, #f4f8ff 100%); padding: 32px 14px; font-family: ''Segoe UI'', Roboto, ''Helvetica Neue'', Arial, sans-serif; color: #334155; }
.email-wrapper { max-width: 640px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 30px rgba(37, 99, 235, 0.12); }
.email-header { background: #2563eb; background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 55%, #3b82f6 100%); padding: 36px 32px; text-align: center; }
.email-header img { max-height: 60px; max-width: 200px; display: inline-block; }
.email-header-name { color: #ffffff; font-size: 22px; font-weight: 700; margin-top: 10px; letter-spacing: .3px; }
.email-body { padding: 36px 34px; font-size: 15px; line-height: 1.65; color: #334155; }
.email-body p { margin: 0 0 15px; }
.email-body strong { color: #1e293b; }
.email-body h2 { font-size: 16px; color: #1e3a8a; margin: 26px 0 12px; padding-bottom: 8px; border-bottom: 2px solid #dbeafe; }
.email-body table.dados { width: 100%; border-collapse: collapse; margin: 10px 0 20px; }
.email-body table.dados td { padding: 11px 14px; border-bottom: 1px solid #eef2fb; font-size: 14px; }
.email-body table.dados td.rotulo { color: #64748b; width: 40%; font-weight: 600; }
.email-body table.itens { width: 100%; border-collapse: collapse; margin: 12px 0 20px; font-size: 14px; border-radius: 10px; overflow: hidden; }
.email-body table.itens th { background: #eff6ff; text-align: left; padding: 12px 14px; color: #1e3a8a; font-weight: 700; }
.email-body table.itens td { padding: 12px 14px; border-bottom: 1px solid #eef2fb; }
.email-body .total { font-size: 18px; color: #1e3a8a; text-align: right; margin-top: 8px; font-weight: 700; }
.btn-pagar { display: inline-block; background: #2563eb; background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%); color: #ffffff !important; text-decoration: none; padding: 14px 30px; border-radius: 10px; font-weight: 700; margin: 8px 8px 8px 0; box-shadow: 0 6px 16px rgba(37, 99, 235, 0.30); }
.btn-link { display: inline-block; background: #1e3a8a; color: #ffffff !important; text-decoration: none; padding: 14px 30px; border-radius: 10px; font-weight: 700; margin: 8px 8px 8px 0; }
.box-pagamento { background: #f4f8ff; border: 1px solid #dbeafe; border-radius: 12px; padding: 18px; margin: 16px 0; }
.box-pagamento .rotulo { color: #2563eb; font-size: 12px; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 6px; font-weight: 700; }
.box-pagamento code { display: block; word-break: break-all; background: #ffffff; border: 1px solid #dbeafe; border-radius: 8px; padding: 12px; font-size: 12px; color: #334155; }
.email-footer { background: #f4f8ff; padding: 24px 32px; text-align: center; font-size: 12px; color: #7b8aa5; line-height: 1.7; border-top: 1px solid #e7eefc; }
.email-footer strong { color: #1e3a8a; }'
WHERE `config` = 'email_css';
