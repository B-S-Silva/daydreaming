import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { BrowserRouter, Link, Route, Routes } from "react-router";

const queryClient = new QueryClient();

function StarterPage() {
  return (
    <main>
      <h1>DayDreaming</h1>
      <p>Preparação para exames internacionais.</p>
      <Link to="/exames-internacionais">Conheça os exames</Link>
    </main>
  );
}

export function App() {
  return (
    <QueryClientProvider client={queryClient}>
      <BrowserRouter>
        <Routes>
          <Route path="*" element={<StarterPage />} />
        </Routes>
      </BrowserRouter>
    </QueryClientProvider>
  );
}
