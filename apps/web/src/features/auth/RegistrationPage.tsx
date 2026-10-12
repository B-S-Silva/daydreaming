import { zodResolver } from "@hookform/resolvers/zod";
import { useState } from "react";
import { useForm } from "react-hook-form";
import { Link } from "react-router";
import { RegistrationInputSchema, type RegistrationInput } from "@daydreaming/shared";
import { AuthPageLayout } from "./AuthPageLayout.js";

export function RegistrationPage() {
  const [notAvailable, setNotAvailable] = useState(false);
  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<RegistrationInput>({
    resolver: zodResolver(RegistrationInputSchema),
    defaultValues: {
      name: "",
      username: "",
      email: "",
      password: "",
      confirmPassword: "",
    },
  });

  return (
    <AuthPageLayout title="CADASTRO">
      <p className="auth-storage-notice" role="note">
        O cadastro ficará disponível quando o banco de dados for configurado. Nenhum dado deste
        formulário será enviado ou armazenado nesta etapa.
      </p>
      {notAvailable && (
        <p className="auth-form-error" role="status">
          O banco de dados ainda não está configurado; a conta não foi criada.
        </p>
      )}
      <form className="auth-form" noValidate onSubmit={handleSubmit(() => setNotAvailable(true))}>
        <label>
          Nome completo
          <input
            autoComplete="name"
            type="text"
            {...register("name")}
            aria-invalid={Boolean(errors.name)}
          />
          {errors.name?.message && <span className="field-error">{errors.name.message}</span>}
        </label>
        <label>
          Nome de usuário
          <input
            autoComplete="username"
            type="text"
            {...register("username")}
            aria-invalid={Boolean(errors.username)}
          />
          {errors.username?.message && (
            <span className="field-error">{errors.username.message}</span>
          )}
        </label>
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
        <label>
          Senha
          <input
            autoComplete="new-password"
            type="password"
            {...register("password")}
            aria-invalid={Boolean(errors.password)}
          />
          {errors.password?.message && (
            <span className="field-error">{errors.password.message}</span>
          )}
        </label>
        <label>
          Confirmar senha
          <input
            autoComplete="new-password"
            type="password"
            {...register("confirmPassword")}
            aria-invalid={Boolean(errors.confirmPassword)}
          />
          {errors.confirmPassword?.message && (
            <span className="field-error">{errors.confirmPassword.message}</span>
          )}
        </label>
        <button className="button button--primary" type="submit">
          CADASTRAR
        </button>
      </form>
      <nav className="auth-links" aria-label="Opções da conta">
        <Link to="/login">VOLTAR PARA LOGIN</Link>
      </nav>
    </AuthPageLayout>
  );
}
