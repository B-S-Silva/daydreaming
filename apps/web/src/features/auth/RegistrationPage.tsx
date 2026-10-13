import { zodResolver } from "@hookform/resolvers/zod";
import { useState } from "react";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { useForm } from "react-hook-form";
import { Link, useNavigate } from "react-router";
import { RegistrationInputSchema, type RegistrationInput } from "@daydreaming/shared";
import { AuthPageLayout } from "./AuthPageLayout.js";
import { AuthApiError, register as registerAccount } from "./auth-api.js";

export function RegistrationPage() {
  const [requestError, setRequestError] = useState<string | null>(null);
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const registrationMutation = useMutation({
    mutationFn: registerAccount,
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["auth-me"] });
      navigate("/login");
    },
    onError: (error: Error) => {
      setRequestError(
        error instanceof AuthApiError ? error.message : "Não foi possível concluir o cadastro.",
      );
    },
  });
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
          registrationMutation.mutate(values);
        })}
      >
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
        <button
          className="button button--primary"
          disabled={registrationMutation.isPending}
          type="submit"
        >
          {registrationMutation.isPending ? "CADASTRANDO..." : "CADASTRAR"}
        </button>
      </form>
      <nav className="auth-links" aria-label="Opções da conta">
        <Link to="/login">VOLTAR PARA LOGIN</Link>
      </nav>
    </AuthPageLayout>
  );
}
