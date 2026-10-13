import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { useState } from "react";
import { BrowserRouter, Route, Routes } from "react-router";
import { LoginPage } from "./features/auth/LoginPage.js";
import { PasswordRecoveryPage } from "./features/auth/PasswordRecoveryPage.js";
import { RegistrationPage } from "./features/auth/RegistrationPage.js";
import { ExamCatalogPage } from "./features/public/ExamCatalogPage.js";
import { CountryGuidesPage } from "./features/public/CountryGuidesPage.js";
import { AboutPage } from "./features/public/AboutPage.js";
import { UniversityRankingPage } from "./features/public/UniversityRankingPage.js";
import "./features/public/university-ranking.css";
import { StudyToolsPage } from "./features/study/StudyToolsPage.js";
import { StudentProfilePage } from "./features/study/StudentProfilePage.js";
import { HomePage } from "./features/public/HomePage.js";
import { QuestionAdminPage } from "./features/questions/QuestionAdminPage.js";
import { ForumPage } from "./features/forum/ForumPage.js";
import { ForumAdminPage } from "./features/forum/ForumAdminPage.js";
import { NotificationsPage } from "./features/notifications/NotificationsPage.js";
import "./features/notifications/notifications.css";
import {
  SimulatorCatalogPage,
  SimulatorOverviewPage,
  SimulatorSessionPage,
} from "./features/simulators/SimulatorPages.js";
import {
  SessionHistoryPage,
  TestHistoryPage,
  TestResultPage,
  TestReviewPage,
} from "./features/results/ResultsPages.js";
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
          <Route path="/guias-paises" element={<CountryGuidesPage />} />
          <Route path="/guias-paises/:id" element={<CountryGuidesPage />} />
          <Route path="/quem-somos" element={<AboutPage />} />
          <Route path="/ranking" element={<UniversityRankingPage />} />
          <Route path="/ferramentas-estudo" element={<StudyToolsPage />} />
          <Route path="/perfil" element={<StudentProfilePage />} />
          <Route path="/login" element={<LoginPage />} />
          <Route path="/cadastro" element={<RegistrationPage />} />
          <Route path="/recuperar-senha" element={<PasswordRecoveryPage />} />
          <Route path="/admin/questoes" element={<QuestionAdminPage />} />
          <Route path="/forum" element={<ForumPage />} />
          <Route path="/forum/topics/:id" element={<ForumPage />} />
          <Route path="/admin/forum" element={<ForumAdminPage />} />
          <Route path="/notificacoes" element={<NotificationsPage />} />
          <Route path="/simulador" element={<SimulatorCatalogPage />} />
          <Route path="/simulador/:tipo" element={<SimulatorOverviewPage />} />
          <Route path="/simulador/sessoes/:id" element={<SimulatorSessionPage />} />
          <Route path="/resultados/:id" element={<TestResultPage />} />
          <Route path="/resultados/:id/revisao" element={<TestReviewPage />} />
          <Route path="/historico/testes" element={<TestHistoryPage />} />
          <Route path="/historico/provas" element={<SessionHistoryPage />} />
          <Route path="*" element={<HomePage />} />
        </Routes>
      </BrowserRouter>
    </QueryClientProvider>
  );
}
