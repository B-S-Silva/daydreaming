import { cleanup, fireEvent, render, screen, waitFor } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";
import { App } from "../../App.js";

interface MockAuthState {
  authenticated: boolean;
  flashMessage: string | null;
}

function jsonResponse(body: unknown, status = 200): Response {
  return new Response(JSON.stringify(body), {
    status,
    headers: { "content-type": "application/json" },
  });
}

function mockAuthApi() {
  const state: MockAuthState = { authenticated: false, flashMessage: null };
  const fetchMock = vi.fn(
    async (input: RequestInfo | URL, init?: RequestInit): Promise<Response> => {
      const path = String(input);
      if (path === "/api/auth/me") {
        return jsonResponse({
          authenticated: state.authenticated,
          ...(state.authenticated
            ? { user: { id: 1, name: "Test User", username: "aluno", isAdmin: false } }
            : {}),
          csrfToken: "a".repeat(64),
          flashMessage: state.flashMessage,
        });
      }

      if (path === "/api/auth/login" && init?.method === "POST") {
        state.authenticated = true;
        return jsonResponse({
          success: true,
          message: "Login realizado com sucesso.",
          user: { id: 1, name: "Test User", username: "aluno", isAdmin: false },
        });
      }

      if (path === "/api/auth/register" && init?.method === "POST") {
        state.flashMessage = "Cadastro realizado com sucesso! Faça login para continuar.";
        return jsonResponse({ success: true, message: state.flashMessage }, 201);
      }

      if (path === "/api/auth/logout" && init?.method === "POST") {
        state.authenticated = false;
        state.flashMessage = "Logout realizado com sucesso!";
        return jsonResponse({ success: true, message: state.flashMessage });
      }

      return jsonResponse({ message: "Not found" }, 404);
    },
  );
  vi.stubGlobal("fetch", fetchMock);
  return { fetchMock, state };
}

function renderAt(path: string) {
  window.history.pushState({}, "", path);
  render(<App />);
}

afterEach(() => {
  cleanup();
  vi.unstubAllGlobals();
  window.history.pushState({}, "", "/");
});

describe("authentication screens", () => {
  it("validates PHP empty semantics and does not submit invalid credentials", async () => {
    const { fetchMock } = mockAuthApi();
    renderAt("/login");

    fireEvent.change(screen.getByLabelText("Usuário"), { target: { value: "0" } });
    fireEvent.change(screen.getByLabelText("Senha"), { target: { value: "senha123" } });
    fireEvent.click(screen.getByRole("button", { name: "ENTRAR" }));

    expect(await screen.findByText("Por favor, preencha todos os campos.")).toBeTruthy();
    await waitFor(() => {
      expect(fetchMock.mock.calls.every(([path]) => path === "/api/auth/me")).toBe(true);
    });
  });

  it("submits valid credentials and updates the public header after login", async () => {
    const { fetchMock } = mockAuthApi();
    renderAt("/login");

    fireEvent.change(screen.getByLabelText("Usuário"), { target: { value: "aluno" } });
    fireEvent.change(screen.getByLabelText("Senha"), { target: { value: "senha123" } });
    fireEvent.click(screen.getByRole("button", { name: "ENTRAR" }));

    expect(await screen.findByText("✅ Olá, Test User")).toBeTruthy();
    expect(fetchMock.mock.calls.some(([path]) => path === "/api/auth/login")).toBe(true);
  });

  it("shows server-side login errors", async () => {
    const { fetchMock } = mockAuthApi();
    fetchMock.mockImplementation(async (input, init) => {
      if (String(input) === "/api/auth/login" && init?.method === "POST") {
        return jsonResponse({ message: "Usuário ou senha incorretos." }, 401);
      }
      if (String(input) === "/api/auth/me") {
        return jsonResponse({
          authenticated: false,
          csrfToken: "a".repeat(64),
          flashMessage: null,
        });
      }
      return jsonResponse({ message: "Not found" }, 404);
    });
    renderAt("/login");

    fireEvent.change(screen.getByLabelText("Usuário"), { target: { value: "aluno" } });
    fireEvent.change(screen.getByLabelText("Senha"), { target: { value: "senha123" } });
    fireEvent.click(screen.getByRole("button", { name: "ENTRAR" }));

    expect(await screen.findByText("Usuário ou senha incorretos.")).toBeTruthy();
  });

  it("validates registration locally and submits valid registration to the API", async () => {
    const { fetchMock } = mockAuthApi();
    renderAt("/cadastro");
    fireEvent.click(screen.getByRole("button", { name: "CADASTRAR" }));

    expect(await screen.findByText("O nome é obrigatório.")).toBeTruthy();
    expect(screen.getByText("O nome de usuário é obrigatório.")).toBeTruthy();
    expect(screen.getByText("O e-mail é obrigatório.")).toBeTruthy();
    expect(screen.getByText("A senha é obrigatória.")).toBeTruthy();
    expect(fetchMock.mock.calls.every(([path]) => path === "/api/auth/me")).toBe(true);

    const values = [
      ["Nome completo", "Nome de Teste"],
      ["Nome de usuário", "novapessoa"],
      ["E-mail", "nova@example.com"],
      ["Senha", "senha123"],
      ["Confirmar senha", "senha123"],
    ] as const;
    for (const [label, value] of values) {
      fireEvent.change(screen.getByLabelText(new RegExp(`^${label}`)), {
        target: { value },
      });
    }
    fireEvent.click(screen.getByRole("button", { name: "CADASTRAR" }));

    expect(
      await screen.findByText("Cadastro realizado com sucesso! Faça login para continuar."),
    ).toBeTruthy();
    expect(fetchMock.mock.calls.some(([path]) => path === "/api/auth/register")).toBe(true);
  });

  it("shows duplicate username or email errors returned by the server", async () => {
    const { fetchMock } = mockAuthApi();
    fetchMock.mockImplementation(async (input, init) => {
      if (String(input) === "/api/auth/register" && init?.method === "POST") {
        return jsonResponse({ message: "Este nome de usuário já está em uso." }, 409);
      }
      if (String(input) === "/api/auth/me") {
        return jsonResponse({
          authenticated: false,
          csrfToken: "a".repeat(64),
          flashMessage: null,
        });
      }
      return jsonResponse({ message: "Not found" }, 404);
    });
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

    expect(await screen.findByText("Este nome de usuário já está em uso.")).toBeTruthy();
  });

  it("does not claim password recovery was sent or contact the recovery endpoint", async () => {
    const { fetchMock } = mockAuthApi();
    renderAt("/recuperar-senha");

    fireEvent.change(screen.getByLabelText("E-mail"), {
      target: { value: "aluno@example.com" },
    });
    fireEvent.click(screen.getByRole("button", { name: "ENVIAR" }));

    expect(await screen.findByText(/a recuperação não está disponível/i)).toBeTruthy();
    expect(fetchMock.mock.calls.every(([path]) => path === "/api/auth/me")).toBe(true);
    expect(screen.getByText(/não afirma que uma mensagem foi enviada/i)).toBeTruthy();
  });
});
