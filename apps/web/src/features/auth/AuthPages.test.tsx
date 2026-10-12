import { cleanup, fireEvent, render, screen } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";
import { App } from "../../App.js";

function renderAt(path: string) {
  window.history.pushState({}, "", path);
  render(<App />);
}

afterEach(() => {
  cleanup();
  vi.unstubAllGlobals();
  window.history.pushState({}, "", "/");
});

describe("authentication screens without a configured database", () => {
  it("validates PHP empty semantics and never sends login credentials", async () => {
    const fetchMock = vi.fn();
    vi.stubGlobal("fetch", fetchMock);
    renderAt("/login");

    fireEvent.change(screen.getByLabelText("Usuário"), {
      target: { value: "0" },
    });
    fireEvent.change(screen.getByLabelText("Senha"), {
      target: { value: "senha123" },
    });
    fireEvent.click(screen.getByRole("button", { name: "ENTRAR" }));

    expect(await screen.findByText("Por favor, preencha todos os campos.")).toBeTruthy();
    expect(fetchMock).not.toHaveBeenCalled();
  });

  it("does not send a valid login before the database is configured", async () => {
    const fetchMock = vi.fn();
    vi.stubGlobal("fetch", fetchMock);
    renderAt("/login");

    fireEvent.change(screen.getByLabelText("Usuário"), {
      target: { value: "aluno" },
    });
    fireEvent.change(screen.getByLabelText("Senha"), {
      target: { value: "senha123" },
    });
    fireEvent.click(screen.getByRole("button", { name: "ENTRAR" }));

    expect(await screen.findByText(/não foi feito login/i)).toBeTruthy();
    expect(fetchMock).not.toHaveBeenCalled();
  });

  it("shows registration validation errors and keeps submission local", async () => {
    const fetchMock = vi.fn();
    vi.stubGlobal("fetch", fetchMock);
    renderAt("/cadastro");

    fireEvent.click(screen.getByRole("button", { name: "CADASTRAR" }));

    expect(await screen.findByText("O nome é obrigatório.")).toBeTruthy();
    expect(screen.getByText("O nome de usuário é obrigatório.")).toBeTruthy();
    expect(screen.getByText("O e-mail é obrigatório.")).toBeTruthy();
    expect(screen.getByText("A senha é obrigatória.")).toBeTruthy();
    expect(fetchMock).not.toHaveBeenCalled();
  });

  it("does not persist a valid registration before the database is configured", async () => {
    const fetchMock = vi.fn();
    vi.stubGlobal("fetch", fetchMock);
    renderAt("/cadastro");

    const values = [
      ["Nome completo", "Nome de Teste"],
      ["Nome de usuário", "aluno"],
      ["E-mail", "aluno@example.com"],
      ["Senha", "senha123"],
      ["Confirmar senha", "senha123"],
    ] as const;
    for (const [label, value] of values) {
      fireEvent.change(screen.getByLabelText(label), { target: { value } });
    }
    fireEvent.click(screen.getByRole("button", { name: "CADASTRAR" }));

    expect(await screen.findByText(/a conta não foi criada/i)).toBeTruthy();
    expect(fetchMock).not.toHaveBeenCalled();
  });

  it("does not claim password recovery was sent or contact an API", async () => {
    const fetchMock = vi.fn();
    vi.stubGlobal("fetch", fetchMock);
    renderAt("/recuperar-senha");

    fireEvent.change(screen.getByLabelText("E-mail"), {
      target: { value: "aluno@example.com" },
    });
    fireEvent.click(screen.getByRole("button", { name: "ENVIAR" }));

    expect(await screen.findByText(/recuperação não está disponível/i)).toBeTruthy();
    expect(fetchMock).not.toHaveBeenCalled();
    expect(screen.getByText(/não afirma que uma mensagem foi enviada/i)).toBeTruthy();
  });
});
