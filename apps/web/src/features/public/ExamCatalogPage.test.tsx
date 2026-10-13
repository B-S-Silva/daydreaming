import { cleanup, fireEvent, render, screen } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";
import { App } from "../../App.js";

const catalog = {
  countries: [
    {
      id: "usa",
      name: "Estados Unidos",
      flag: "🇺🇸",
      continent: "america",
      requirements: ["Graduação: SAT ou ACT"],
    },
    {
      id: "germany",
      name: "Alemanha",
      flag: "🇩🇪",
      continent: "europa",
      requirements: ["Alemão: TestDaF ou DSH"],
    },
  ],
  exams: [],
  regionalGuides: [],
  tips: [],
};

function mockCatalogResponse(value: unknown = catalog) {
  vi.stubGlobal(
    "fetch",
    vi.fn(async (input: RequestInfo | URL) => {
      const path = String(input);
      if (path === "/api/auth/me") {
        return new Response(
          JSON.stringify({
            authenticated: false,
            csrfToken: "a".repeat(64),
            flashMessage: null,
          }),
          { status: 200, headers: { "Content-Type": "application/json" } },
        );
      }
      return new Response(JSON.stringify(value), {
        status: 200,
        headers: { "Content-Type": "application/json" },
      });
    }),
  );
}

function openCatalog() {
  window.history.pushState({}, "", "/exames-internacionais");
  render(<App />);
}

afterEach(() => {
  cleanup();
  vi.unstubAllGlobals();
  window.history.pushState({}, "", "/");
});

describe("public pages", () => {
  it("renders the home page and navigates to the public catalog", async () => {
    mockCatalogResponse();
    window.history.pushState({}, "", "/");
    render(<App />);

    fireEvent.click(screen.getByRole("link", { name: /Explorar Testes/ }));

    await screen.findByRole("heading", {
      name: "Guia Completo de Testes Internacionais",
    });
    await screen.findByRole("heading", { name: /Estados Unidos/ });
    expect(screen.getByRole("heading", { name: /Estados Unidos/ })).toBeTruthy();
  });

  it("shows loading and error states for the catalog request", async () => {
    vi.stubGlobal(
      "fetch",
      vi.fn((input: RequestInfo | URL) =>
        String(input) === "/api/auth/me"
          ? Promise.resolve(
              new Response(
                JSON.stringify({
                  authenticated: false,
                  csrfToken: "a".repeat(64),
                  flashMessage: null,
                }),
                { status: 200 },
              ),
            )
          : new Promise<Response>(() => {}),
      ),
    );
    openCatalog();
    expect(screen.getByRole("status").textContent).toBe("Carregando catálogo...");

    cleanup();
    vi.stubGlobal(
      "fetch",
      vi.fn((input: RequestInfo | URL) =>
        String(input) === "/api/auth/me"
          ? Promise.resolve(
              new Response(
                JSON.stringify({
                  authenticated: false,
                  csrfToken: "a".repeat(64),
                  flashMessage: null,
                }),
                { status: 200 },
              ),
            )
          : Promise.reject(new Error("API offline")),
      ),
    );
    openCatalog();
    expect(await screen.findByText("API offline")).toBeTruthy();
  });

  it("filters by continent and country and shows the empty state", async () => {
    mockCatalogResponse();
    openCatalog();
    await screen.findByRole("heading", { name: /Estados Unidos/ });

    fireEvent.click(screen.getByRole("button", { name: "🌎 América" }));
    expect(screen.getByRole("heading", { name: /Estados Unidos/ })).toBeTruthy();
    expect(screen.queryByRole("heading", { name: /Alemanha/ })).toBeNull();

    fireEvent.change(screen.getByLabelText("Filtrar por país"), {
      target: { value: "usa" },
    });
    expect(screen.getByRole("heading", { name: /Estados Unidos/ })).toBeTruthy();

    mockCatalogResponse({
      countries: [],
      exams: [],
      regionalGuides: [],
      tips: [],
    });
    cleanup();
    openCatalog();
    expect(
      await screen.findByText("Nenhuma informação de exames está disponível no momento."),
    ).toBeTruthy();
  });

  it("asks guests to log in before opening a simulator", async () => {
    mockCatalogResponse();
    openCatalog();
    await screen.findByRole("heading", { name: /Estados Unidos/ });
    vi.spyOn(window, "alert").mockImplementation(() => {});
    vi.spyOn(window, "confirm").mockReturnValue(false);

    fireEvent.click(screen.getAllByRole("button", { name: "Simular Teste" })[0]!);

    expect(window.alert).toHaveBeenCalledWith("Você precisa estar logado para prosseguir!");
    expect(window.confirm).toHaveBeenCalledWith("Deseja efetuar o login?");
  });
});
