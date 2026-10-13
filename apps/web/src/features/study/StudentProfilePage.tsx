import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useEffect, useState } from "react";
import type { FormEvent } from "react";
import { Link } from "react-router";
import type { UpdateStudentProfileInput } from "@daydreaming/shared";
import { PublicHeader } from "../auth/AuthPageLayout.js";
import {
  getStudentBadges,
  getStudentProfile,
  StudyApiError,
  updateStudentProfile,
} from "./study-api.js";

const emptyProfile: UpdateStudentProfileInput = {
  school: null,
  grade: null,
  location: null,
  gpa: null,
  languages: [],
  exams: [],
  interests: null,
  bio: null,
  countryTrackingEnabled: false,
};

function commaSeparated(value: string): string[] {
  return value
    .split(",")
    .map((item) => item.trim())
    .filter(Boolean);
}

export function StudentProfilePage() {
  const queryClient = useQueryClient();
  const profileQuery = useQuery({
    queryKey: ["student-profile"],
    queryFn: getStudentProfile,
  });
  const badgesQuery = useQuery({
    queryKey: ["student-badges"],
    queryFn: getStudentBadges,
  });
  const [form, setForm] = useState(emptyProfile);
  const [languagesText, setLanguagesText] = useState("");
  const [examsText, setExamsText] = useState("");
  const saveMutation = useMutation({
    mutationFn: updateStudentProfile,
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["student-profile"] });
    },
  });

  useEffect(() => {
    if (profileQuery.data) {
      setForm(profileQuery.data.profile);
      setLanguagesText(profileQuery.data.profile.languages.join(", "));
      setExamsText(profileQuery.data.profile.exams.join(", "));
    }
  }, [profileQuery.data]);

  if (profileQuery.isPending) {
    return (
      <>
        <PublicHeader />
        <main className="study-page content-width">
          <p role="status">Carregando perfil...</p>
        </main>
      </>
    );
  }

  if (profileQuery.error) {
    return (
      <>
        <PublicHeader />
        <main className="study-page content-width">
          <h1>Perfil acadêmico</h1>
          <p role="alert">
            {profileQuery.error instanceof StudyApiError
              ? profileQuery.error.message
              : "Não foi possível carregar seu perfil."}
          </p>
          {profileQuery.error instanceof StudyApiError && profileQuery.error.statusCode === 401 && (
            <Link className="button button--primary" to="/login">
              Fazer login
            </Link>
          )}
        </main>
      </>
    );
  }

  function updateField<K extends keyof UpdateStudentProfileInput>(
    field: K,
    value: UpdateStudentProfileInput[K],
  ) {
    setForm((current) => ({ ...current, [field]: value }));
  }

  function submitProfile(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    saveMutation.mutate({
      ...form,
      languages: commaSeparated(languagesText),
      exams: commaSeparated(examsText),
    });
  }

  return (
    <>
      <PublicHeader />
      <main className="study-page content-width">
        <header className="results-heading">
          <h1>Meu perfil acadêmico</h1>
          <p>Os dados deste perfil são privados e visíveis apenas para você.</p>
        </header>
        <form className="profile-form" onSubmit={submitProfile}>
          <label>
            Escola ou instituição
            <input
              maxLength={150}
              onChange={(event) => updateField("school", event.target.value || null)}
              value={form.school ?? ""}
            />
          </label>
          <label>
            Série ou etapa acadêmica
            <input
              maxLength={50}
              onChange={(event) => updateField("grade", event.target.value || null)}
              value={form.grade ?? ""}
            />
          </label>
          <label>
            Cidade ou região
            <input
              maxLength={150}
              onChange={(event) => updateField("location", event.target.value || null)}
              value={form.location ?? ""}
            />
          </label>
          <label>
            Idiomas (separados por vírgula)
            <input
              onChange={(event) => setLanguagesText(event.target.value)}
              value={languagesText}
            />
          </label>
          <label>
            Exames realizados (separados por vírgula)
            <input onChange={(event) => setExamsText(event.target.value)} value={examsText} />
          </label>
          <label>
            Áreas de interesse
            <input
              maxLength={255}
              onChange={(event) => updateField("interests", event.target.value || null)}
              value={form.interests ?? ""}
            />
          </label>
          <label>
            Biografia
            <textarea
              maxLength={2000}
              onChange={(event) => updateField("bio", event.target.value || null)}
              rows={4}
              value={form.bio ?? ""}
            />
          </label>
          <label className="consent-check">
            <input
              checked={form.countryTrackingEnabled}
              onChange={(event) => updateField("countryTrackingEnabled", event.target.checked)}
              type="checkbox"
            />
            Permito registrar os guias de países que abro para acompanhar meu próprio progresso.
          </label>
          <p className="history-note">
            O rastreamento fica desligado até você ativá-lo e pode ser desativado a qualquer
            momento. Ele registra abertura de guia, não visita física.
          </p>
          {saveMutation.error && (
            <p role="alert">
              {saveMutation.error instanceof StudyApiError
                ? saveMutation.error.message
                : "Não foi possível salvar o perfil."}
            </p>
          )}
          {saveMutation.isSuccess && <p role="status">{saveMutation.data.message}</p>}
          <button
            className="button button--primary"
            disabled={saveMutation.isPending}
            type="submit"
          >
            {saveMutation.isPending ? "Salvando..." : "Salvar perfil"}
          </button>
        </form>
        <section className="profile-badges" id="badges">
          <header>
            <h2>🏆 Minhas conquistas</h2>
            <p>
              Conquistas calculadas a partir das suas provas, participação, GPA e guias abertos.
            </p>
          </header>
          {badgesQuery.isPending && <p role="status">Carregando conquistas...</p>}
          {badgesQuery.error && (
            <p role="alert">
              {badgesQuery.error instanceof StudyApiError
                ? badgesQuery.error.message
                : "Não foi possível carregar suas conquistas."}
            </p>
          )}
          {badgesQuery.data?.items.length === 0 && (
            <p>
              Nenhuma badge conquistada ainda. Complete provas, participe do fórum, registre seu GPA
              e explore os guias para desbloquear conquistas.
            </p>
          )}
          <div className="profile-badges__grid">
            {badgesQuery.data?.items.map((badge) => (
              <article className={`profile-badge profile-badge--${badge.rarity}`} key={badge.code}>
                <img
                  className="profile-badge__image"
                  src={`/images/badge-${badge.code.replace(/_/g, "-")}.jpeg`}
                  alt={badge.name}
                  loading="lazy"
                  onError={(e) => {
                    (e.currentTarget as HTMLImageElement).style.display = "none";
                  }}
                />
                <span aria-hidden="true">{badge.icon}</span>
                <h3>{badge.name}</h3>
                <p>{badge.description}</p>
                <time dateTime={badge.acquiredAt}>
                  {new Intl.DateTimeFormat("pt-BR", { dateStyle: "short" }).format(
                    new Date(badge.acquiredAt),
                  )}
                </time>
                {badge.context && <small>{badge.context}</small>}
              </article>
            ))}
          </div>
        </section>
      </main>
    </>
  );
}
