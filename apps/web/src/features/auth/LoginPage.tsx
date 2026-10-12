import { zodResolver } from "@hookform/resolvers/zod";
import { useState } from "react";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { useForm } from "react-hook-form";
import { Link, useNavigate } from "react-router";
import { LoginInputSchema, type LoginInput } from "@daydreaming/shared";
import { AuthPageLayout } from "./AuthPageLayout.js";
import { AuthApiError, login } from "./auth-api.js";

export function LoginPage() {
  const [requestError, setRequestError] = useState<string | null>(null);
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const loginMutation = useMutation({
    mutationFn: login,
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["auth-me"] });
      navigate("/");
    },
    onError: (error: Error) => {
      setRequestError(
        error instanceof AuthApiError ? error.message : "Não foi possível fazer login.",
      );
    },
  });
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
      {requestError && (
        <p className="auth-form-error" role="alert">
          {requestError}
        </p>
      )}
      <form
        className="auth-form"
        noValidate
        onSubmit={handleSubmit((values) => {
          setRequestError(null);
          loginMutation.mutate(values);
        })}
      >
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
        <button className="button button--primary" disabled={loginMutation.isPending} type="submit">
          {loginMutation.isPending ? "ENTRANDO..." : "ENTRAR"}
        </button>
      </form>
      <nav className="auth-links" aria-label="Opções da conta">
        <Link to="/cadastro">CADASTRAR</Link>
        <Link to="/recuperar-senha">ESQUECI A SENHA</Link>
      </nav>
    </AuthPageLayout>
  );
}
