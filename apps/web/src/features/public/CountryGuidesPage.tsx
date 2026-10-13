import { useMutation, useQuery } from "@tanstack/react-query";
import { useMemo, useState, useEffect, useRef } from "react";
import { Link, useParams } from "react-router";
import { getCurrentUser } from "../auth/auth-api.js";
import { PublicHeader } from "../auth/AuthPageLayout.js";
import { getCountryGuide, getCountryGuides } from "./countryGuidesApi.js";
import { recordCountryExploration } from "../study/study-api.js";

export function CountryGuidesPage() {
  const { id } = useParams();
  return id ? <CountryGuideDetail id={id} /> : <CountryGuideDirectory />;
}

function CountryGuideDirectory() {
  const [search, setSearch] = useState("");
  const { data, error, isPending } = useQuery({
    queryKey: ["country-guides"],
    queryFn: getCountryGuides,
  });
  const items = useMemo(
    () =>
      data?.items.filter((item) =>
        `${item.name} ${item.description} ${item.highlights.join(" ")}`
          .toLocaleLowerCase("pt-BR")
          .includes(search.trim().toLocaleLowerCase("pt-BR")),
      ) ?? [],
    [data, search],
  );

  return (
    <>
      <PublicHeader />
      <main className="country-guides-page content-width">
        <header className="results-heading">
          <h1>Guias para estudar no exterior</h1>
          <p>Compare caminhos de candidatura e confira sempre os requisitos nas fontes oficiais.</p>
        </header>
        <label className="guide-search">
          <span>Buscar país</span>
          <input
            value={search}
            onChange={(event) => setSearch(event.target.value)}
            placeholder="Ex.: Alemanha"
            type="search"
          />
        </label>
        {isPending && <p role="status">Carregando guias...</p>}
        {error && <p role="alert">{error.message}</p>}
        {data && items.length === 0 && (
          <p className="results-empty" role="status">
            Nenhum guia corresponde à busca.
          </p>
        )}
        <div className="guide-grid">
          {items.map((guide) => (
            <article className="guide-card" key={guide.id}>
              <div className="guide-card__image-wrap">
                <img
                  className="guide-card__image"
                  src={`/images/${guide.id}-home.png`}
                  alt={`Estudar em ${guide.name}`}
                  loading="lazy"
                  onError={(e) => {
                    (e.currentTarget as HTMLImageElement).src =
                      `/images/${guide.id}-background.jpg`;
                  }}
                />
              </div>
              <h2>
                <span aria-hidden="true">{guide.flag}</span> {guide.name}
              </h2>
              <p>{guide.description}</p>
              <ul>
                {guide.highlights.map((highlight) => (
                  <li key={highlight}>{highlight}</li>
                ))}
              </ul>
              <Link className="button button--primary" to={`/guias-paises/${guide.id}`}>
                Abrir guia
              </Link>
            </article>
          ))}
        </div>
        <p className="guide-disclaimer">
          Os nomes de programas e orientações gerais foram recuperados de uma versão anterior do
          projeto; não representam confirmação de elegibilidade ou disponibilidade. Confirme prazos,
          valores, vistos e requisitos diretamente com universidades e órgãos oficiais.
        </p>
      </main>
    </>
  );
}

function CountryGuideDetail({ id }: { id: string }) {
  const { data, error, isPending } = useQuery({
    queryKey: ["country-guide", id],
    queryFn: () => getCountryGuide(id),
  });
  const currentUserQuery = useQuery({
    queryKey: ["auth-me"],
    queryFn: getCurrentUser,
  });
  const recordVisitMutation = useMutation({ mutationFn: recordCountryExploration });
  const trackedCountry = useRef<string | null>(null);

  useEffect(() => {
    if (data && currentUserQuery.data?.authenticated && trackedCountry.current !== data.id) {
      trackedCountry.current = data.id;
      recordVisitMutation.mutate(data.id);
    }
  }, [currentUserQuery.data, data, recordVisitMutation.mutate]);

  return (
    <>
      <PublicHeader />
      <main className="country-guides-page content-width">
        <Link to="/guias-paises">← Todos os países</Link>
        {isPending && <p role="status">Carregando guia...</p>}
        {error && <p role="alert">{error.message}</p>}
        {data && (
          <>
            <header className="results-heading">
              <div
                style={{
                  position: "relative",
                  borderRadius: "16px",
                  overflow: "hidden",
                  marginBottom: "24px",
                  maxHeight: "260px",
                  boxShadow: "var(--shadow-md)",
                }}
              >
                <img
                  src={`/images/${data.id}-background.jpg`}
                  alt={`Paisagem de ${data.name}`}
                  style={{ width: "100%", height: "240px", objectFit: "cover" }}
                  onError={(e) => {
                    (e.currentTarget as HTMLImageElement).src = `/images/${data.id}-home.png`;
                  }}
                />
              </div>
              <h1>
                <span aria-hidden="true">{data.flag}</span> {data.name}
              </h1>
              <p>{data.description}</p>
            </header>
            {data.sections.map((section) => (
              <section className="guide-section" key={section.title}>
                <h2>{section.title}</h2>
                {section.paragraphs.map((paragraph) => (
                  <p key={paragraph}>{paragraph}</p>
                ))}
                {currentUserQuery.isPending && <p role="status">Verificando seu progresso...</p>}
                {currentUserQuery.error && (
                  <p role="alert">Não foi possível verificar se o guia pode ser registrado.</p>
                )}
                {currentUserQuery.data?.authenticated && recordVisitMutation.isSuccess && (
                  <p role="status">
                    {recordVisitMutation.data.explored
                      ? "Abertura do guia registrada no seu progresso."
                      : "Para registrar seu progresso, ative o consentimento no perfil."}{" "}
                    <Link to="/perfil">Configurar perfil</Link>
                  </p>
                )}
                {recordVisitMutation.error && (
                  <p role="alert">
                    {recordVisitMutation.error instanceof Error
                      ? recordVisitMutation.error.message
                      : "Não foi possível registrar a abertura do guia."}
                  </p>
                )}
                {currentUserQuery.data?.authenticated === false && (
                  <p>
                    <Link to="/login">Entre</Link> e ative o consentimento no perfil para salvar seu
                    progresso.
                  </p>
                )}
                {section.links.length > 0 && (
                  <ul>
                    {section.links.map((link) => (
                      <li key={link.url}>
                        <a href={link.url} rel="noreferrer" target="_blank">
                          {link.label}
                        </a>
                      </li>
                    ))}
                  </ul>
                )}
              </section>
            ))}
            <p className="guide-disclaimer">
              Conteúdo legado sujeito a mudanças. Confirme informações atuais diretamente com
              universidades, bolsas e órgãos governamentais oficiais.
            </p>
          </>
        )}
      </main>
    </>
  );
}
