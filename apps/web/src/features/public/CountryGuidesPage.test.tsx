import { cleanup, fireEvent, render, screen } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";
import { App } from "../../App.js";

const guide = {
  id: "alemanha",
  name: "Alemanha",
  flag: "🇩🇪",
  continent: "europa",
  description: "Planeje seus estudos na Alemanha.",
  highlights: ["Pesquise os requisitos oficiais."],
  sections: [
    {
      title: "Planejamento",
      paragraphs: ["Confirme os requisitos junto à universidade."],
      links: [],
    },
  ],
};

function mockApi() {
  vi.stubGlobal(
    "fetch",
    vi.fn(async (input: RequestInfo | URL) => {
      const path = String(input);
      const body =
        path === "/api/auth/me"
          ? { authenticated: false, csrfToken: "a".repeat(64), flashMessage: null }
          : path === "/api/public/country-guides"
            ? { items: [guide] }
            : guide;
      return new Response(JSON.stringify(body), {
        status: 200,
        headers: { "Content-Type": "application/json" },
      });
    }),
  );
}

afterEach(() => {
  cleanup();
  vi.unstubAllGlobals();
  window.history.pushState({}, "", "/");
});

describe("country guides pages", () => {
  it("lists and filters the guide catalog", async () => {
    mockApi();
    window.history.pushState({}, "", "/guias-paises");
    render(<App />);

    await screen.findByRole("heading", { name: "Alemanha" });
    fireEvent.change(screen.getByRole("searchbox", { name: "Buscar país" }), {
      target: { value: "Canadá" },
    });

    expect(await screen.findByText("Nenhum guia corresponde à busca.")).toBeTruthy();
  });

  it("opens the selected country guide detail", async () => {
    mockApi();
    window.history.pushState({}, "", "/guias-paises/alemanha");
    render(<App />);

    expect(await screen.findByRole("heading", { name: /Planejamento/ })).toBeTruthy();
    expect(screen.getByText("Confirme os requisitos junto à universidade.")).toBeTruthy();
  });
});
