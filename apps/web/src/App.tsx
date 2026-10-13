import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { useState } from "react";
import { BrowserRouter, Route, Routes } from "react-router";
import { LoginPage } from "./features/auth/LoginPage.js";
import { PasswordRecoveryPage } from "./features/auth/PasswordRecoveryPage.js";
import { RegistrationPage } from "./features/auth/RegistrationPage.js";
import { ExamCatalogPage } from "./features/public/ExamCatalogPage.js";
import { HomePage } from "./features/public/HomePage.js";
import { QuestionAdminPage } from "./features/questions/QuestionAdminPage.js";
import { ForumPage } from "./features/forum/ForumPage.js";
import { ForumAdminPage } from "./features/forum/ForumAdminPage.js";
import "./styles.css";

export function App() {
  const [queryClient] = useState(
    () =>
      new QueryClient({
        defaultOptions: {
          queries: { retry: false },
        },
      }),
  );

  return (
    <QueryClientProvider client={queryClient}>
      <BrowserRouter>
        <Routes>
          <Route path="/" element={<HomePage />} />
          <Route path="/exames-internacionais" element={<ExamCatalogPage />} />
          <Route path="/login" element={<LoginPage />} />
          <Route path="/cadastro" element={<RegistrationPage />} />
          <Route path="/recuperar-senha" element={<PasswordRecoveryPage />} />
          <Route path="/admin/questoes" element={<QuestionAdminPage />} />
          <Route path="/forum" element={<ForumPage />} />
          <Route path="/forum/topics/:id" element={<ForumPage />} />
          <Route path="/admin/forum" element={<ForumAdminPage />} />
          <Route path="*" element={<HomePage />} />
        </Routes>
      </BrowserRouter>
    </QueryClientProvider>
  );
}
