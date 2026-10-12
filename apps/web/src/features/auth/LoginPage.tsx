import { zodResolver } from "@hookform/resolvers/zod";
import { useState } from "react";
import { useForm } from "react-hook-form";
import { Link } from "react-router";
import { LoginInputSchema, type LoginInput } from "@daydreaming/shared";
import { AuthPageLayout } from "./AuthPageLayout.js";

export function LoginPage() {
  const [notAvailable, setNotAvailable] = useState(false);
  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<LoginInput>({
    resolver: zodResolver(LoginInputSchema),
    defaultValues: { username: "", password: "" },
  });

  return (
    <AuthPageLayout title="LOGIN">
      <p className="auth-storage-notice" role="note">
        A autenticação ficará disponível quando o banco de dados for configurado. Nenhum dado deste
        formulário será enviado ou armazenado nesta etapa.
      </p>
      {notAvailable && (
        <p className="auth-form-error" role="status">
          O banco de dados ainda não está configurado; não foi feito login.
        </p>
      )}
      <form className="auth-form" noValidate onSubmit={handleSubmit(() => setNotAvailable(true))}>
        <label>
          Usuário
          <input
            autoComplete="username"
            type="text"
            {...register("username")}
            aria-invalid={Boolean(errors.username || errors.root)}
          />
        </label>
        <label>
          Senha
          <input
            autoComplete="current-password"
            type="password"
            {...register("password")}
            aria-invalid={Boolean(errors.password || errors.root)}
          />
        </label>
        {errors.root?.message && (
          <p className="auth-form-error" role="alert">
            {errors.root.message}
          </p>
        )}
        <button className="button button--primary" type="submit">
          ENTRAR
        </button>
      </form>
      <nav className="auth-links" aria-label="Opções da conta">
        <Link to="/cadastro">CADASTRAR</Link>
        <Link to="/recuperar-senha">ESQUECI A SENHA</Link>
      </nav>
    </AuthPageLayout>
  );
}
