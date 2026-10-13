import { cleanup, fireEvent, render, screen } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";
import { App } from "../../App.js";

function openTools() {
  vi.stubGlobal(
    "fetch",
    vi.fn(async (input: RequestInfo | URL) => {
      const isAuth = String(input) === "/api/auth/me";
      return new Response(
        JSON.stringify(
          isAuth
            ? { authenticated: false, csrfToken: "a".repeat(64), flashMessage: null }
            : { message: "Faça login para acessar seu perfil." },
        ),
        { status: isAuth ? 200 : 401, headers: { "Content-Type": "application/json" } },
      );
    }),
  );
  window.history.pushState({}, "", "/ferramentas-estudo");
  render(<App />);
}

afterEach(() => {
  cleanup();
  vi.unstubAllGlobals();
  window.history.pushState({}, "", "/");
});

describe("study tools", () => {
  it("calculates GPA and shows the documented approximation", async () => {
    openTools();
    await screen.findByRole("heading", { name: "Calculadora de GPA" });
    fireEvent.change(screen.getByLabelText("Matéria 1"), { target: { value: "Matemática" } });
    fireEvent.change(screen.getByLabelText("Nota (0–10)"), { target: { value: "8" } });

    expect(screen.getByText("3.20 / 4.00")).toBeTruthy();
    expect(
      screen.getByText(/não substitui a equivalência oficial exigida por universidades/i),
    ).toBeTruthy();
  });

  it("shows the exploratory career result for answered quiz items", async () => {
    openTools();
    fireEvent.click(screen.getByRole("button", { name: "Quiz vocacional" }));
    fireEvent.click(
      screen.getAllByLabelText("Organizar, planejar e trabalhar com metas ou dados")[0]!,
    );
    fireEvent.click(screen.getByRole("button", { name: "Ver perfil exploratório" }));

    expect(
      await screen.findByRole("heading", { name: "Gestor(a) / Administrativo(a)" }),
    ).toBeTruthy();
    expect(screen.getByText(/não é uma avaliação profissional validada/i)).toBeTruthy();
  });
});
