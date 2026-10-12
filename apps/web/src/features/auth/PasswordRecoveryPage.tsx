import { zodResolver } from "@hookform/resolvers/zod";
import { useState } from "react";
import { useForm } from "react-hook-form";
import { Link } from "react-router";
import { PasswordRecoveryInputSchema, type PasswordRecoveryInput } from "@daydreaming/shared";
import { AuthPageLayout } from "./AuthPageLayout.js";

export function PasswordRecoveryPage() {
  const [notAvailable, setNotAvailable] = useState(false);
  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<PasswordRecoveryInput>({
    resolver: zodResolver(PasswordRecoveryInputSchema),
    defaultValues: { email: "" },
  });

  return (
    <AuthPageLayout title="RECUPERAR SENHA">
      <p className="auth-storage-notice" role="note">
        O PHP atual não envia e-mail nem persiste tokens, então a recuperação ainda não pode ser
        concluída. Este formulário não afirma que uma mensagem foi enviada.
      </p>
      {notAvailable && (
        <p className="auth-form-error" role="status">
          A recuperação não está disponível: o sistema não envia e-mail nem armazena tokens.
        </p>
      )}
      <form className="auth-form" noValidate onSubmit={handleSubmit(() => setNotAvailable(true))}>
        <label>
          E-mail
          <input
            autoComplete="email"
            type="email"
            {...register("email")}
            aria-invalid={Boolean(errors.email)}
          />
          {errors.email?.message && <span className="field-error">{errors.email.message}</span>}
        </label>
        <button className="button button--primary" type="submit">
          ENVIAR
        </button>
      </form>
      <nav className="auth-links" aria-label="Opções da conta">
        <Link to="/login">VOLTAR PARA LOGIN</Link>
      </nav>
    </AuthPageLayout>
  );
}
