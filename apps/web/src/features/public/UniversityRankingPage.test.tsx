import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { cleanup, fireEvent, render, screen, waitFor } from "@testing-library/react";
import { MemoryRouter } from "react-router";
import { afterEach, describe, expect, it, vi } from "vitest";
import { UniversityRankingPage } from "./UniversityRankingPage.js";

const university = {
  id: "1",
  rank: 1,
  name: "Massachusetts Institute of Technology (MIT)",
  country: "Estados Unidos",
  countryCode: "us",
  region: "americas",
  score: 100,
  logo: "https://example.test/mit.png",
  location: "Cambridge, Massachusetts, Estados Unidos",
  details: [
    {
      title: "Pontuação Geral",
      value: "100.0",
      description: "Ranking: #1",
    },
  ],
};

function jsonResponse(body: unknown, status = 200) {
  return new Response(JSON.stringify(body), {
    status,
    headers: { "Content-Type": "application/json" },
  });
}

function renderPage() {
  const client = new QueryClient({
    defaultOptions: { queries: { retry: false } },
  });
  return render(
    <QueryClientProvider client={client}>
      <MemoryRouter>
        <UniversityRankingPage />
      </MemoryRouter>
    </QueryClientProvider>,
  );
}

function filtersResponse() {
  return { countries: [{ code: "us", name: "Estados Unidos" }], regions: ["americas"] };
}

function listResponse(items = [university]) {
  return { items, page: 1, pageSize: 10, total: items.length };
}

afterEach(() => {
  cleanup();
  vi.unstubAllGlobals();
});

describe("UniversityRankingPage", () => {
  it("lists, filters and opens a university detail section", async () => {
    const fetchMock = vi.fn(async (input: RequestInfo | URL) => {
      const path = String(input);
      if (path === "/api/auth/me") {
        return jsonResponse({
          authenticated: false,
          csrfToken: "a".repeat(64),
          flashMessage: null,
        });
      }
      if (path === "/api/public/universities/filters") return jsonResponse(filtersResponse());
      if (path === "/api/public/universities/1") return jsonResponse(university);
      return jsonResponse(listResponse());
    });
    vi.stubGlobal("fetch", fetchMock);

    renderPage();
    expect(await screen.findByText(university.name)).toBeTruthy();

    fireEvent.change(screen.getByRole("combobox", { name: "País" }), {
      target: { value: "us" },
    });
    fireEvent.click(screen.getByRole("button", { name: "Aplicar filtros" }));
    await waitFor(() =>
      expect(fetchMock).toHaveBeenCalledWith(
        expect.stringContaining("/api/public/universities?country=us"),
      ),
    );

    fireEvent.click(await screen.findByRole("button", { name: "Ver detalhes" }));
    expect(await screen.findByText("Ranking: #1")).toBeTruthy();
    expect(screen.getByRole("dialog")).toBeTruthy();
  });

  it("shows loading and API error states", async () => {
    let rejectList: ((response: Response) => void) | undefined;
    vi.stubGlobal(
      "fetch",
      vi.fn((input: RequestInfo | URL) => {
        if (String(input) === "/api/auth/me") {
          return Promise.resolve(
            jsonResponse({ authenticated: false, csrfToken: "a".repeat(64), flashMessage: null }),
          );
        }
        if (String(input) === "/api/public/universities/filters") {
          return Promise.resolve(jsonResponse(filtersResponse()));
        }
        return new Promise<Response>((resolve) => {
          rejectList = resolve;
        });
      }),
    );

    renderPage();
    expect(screen.getByText("Carregando ranking...")).toBeTruthy();
    await waitFor(() => expect(rejectList).toBeDefined());
    rejectList?.(jsonResponse({ message: "Falha ao carregar ranking." }, 503));
    expect((await screen.findByRole("alert")).textContent).toContain("Falha ao carregar ranking.");
  });

  it("shows the empty state when filters match no universities", async () => {
    vi.stubGlobal(
      "fetch",
      vi.fn(async (input: RequestInfo | URL) =>
        String(input) === "/api/auth/me"
          ? jsonResponse({ authenticated: false, csrfToken: "a".repeat(64), flashMessage: null })
          : String(input) === "/api/public/universities/filters"
            ? jsonResponse(filtersResponse())
            : jsonResponse(listResponse([])),
      ),
    );

    renderPage();
    expect(await screen.findByText("Nenhuma universidade corresponde aos filtros.")).toBeTruthy();
  });
});
