import { CountryGuideListResponseSchema, CountryGuideSchema } from "@daydreaming/shared";
import type { CountryGuide, CountryGuideSummary } from "@daydreaming/shared";
import { countryGuideContent } from "./country-guide-content.js";

const guideData: Array<{
  id: string;
  name: string;
  flag: string;
  continent: CountryGuideSummary["continent"];
  scholarship: string;
}> = [
  {
    id: "africa",
    name: "África do Sul",
    flag: "🇿🇦",
    continent: "africa",
    scholarship: "Mandela Rhodes Scholarships",
  },
  { id: "alemanha", name: "Alemanha", flag: "🇩🇪", continent: "europa", scholarship: "DAAD" },
  {
    id: "arabia",
    name: "Arábia Saudita",
    flag: "🇸🇦",
    continent: "asia",
    scholarship: "King Abdullah Scholarship Program",
  },
  {
    id: "australia",
    name: "Austrália",
    flag: "🇦🇺",
    continent: "oceania",
    scholarship: "Australia Awards",
  },
  {
    id: "belgica",
    name: "Bélgica",
    flag: "🇧🇪",
    continent: "europa",
    scholarship: "Study in Belgium",
  },
  {
    id: "canada",
    name: "Canadá",
    flag: "🇨🇦",
    continent: "america",
    scholarship: "Vanier Canada Graduate Scholarships",
  },
  {
    id: "china",
    name: "China",
    flag: "🇨🇳",
    continent: "asia",
    scholarship: "Chinese Government Scholarship",
  },
  {
    id: "coreia",
    name: "Coreia do Sul",
    flag: "🇰🇷",
    continent: "asia",
    scholarship: "Global Korea Scholarship (GKS)",
  },
  {
    id: "dinamarca",
    name: "Dinamarca",
    flag: "🇩🇰",
    continent: "europa",
    scholarship: "Danish Government Scholarships",
  },
  {
    id: "emirados",
    name: "Emirados Árabes Unidos",
    flag: "🇦🇪",
    continent: "asia",
    scholarship: "UAE Government Scholarships",
  },
  {
    id: "espanha",
    name: "Espanha",
    flag: "🇪🇸",
    continent: "europa",
    scholarship: "Fundación Carolina e MAEC-AECID",
  },
  {
    id: "eua",
    name: "Estados Unidos",
    flag: "🇺🇸",
    continent: "america",
    scholarship: "Fulbright Brasil",
  },
  {
    id: "finlandia",
    name: "Finlândia",
    flag: "🇫🇮",
    continent: "europa",
    scholarship: "Finnish Government Scholarship Pool",
  },
  {
    id: "franca",
    name: "França",
    flag: "🇫🇷",
    continent: "europa",
    scholarship: "Eiffel Excellence Scholarship",
  },
  {
    id: "holanda",
    name: "Holanda",
    flag: "🇳🇱",
    continent: "europa",
    scholarship: "Holland Scholarship",
  },
  {
    id: "hungria",
    name: "Hungria",
    flag: "🇭🇺",
    continent: "europa",
    scholarship: "Study in Hungary",
  },
  { id: "india", name: "Índia", flag: "🇮🇳", continent: "asia", scholarship: "ICCR Scholarships" },
  {
    id: "indonesia",
    name: "Indonésia",
    flag: "🇮🇩",
    continent: "asia",
    scholarship: "KNB Scholarship e LPDP",
  },
  {
    id: "irlanda",
    name: "Irlanda",
    flag: "🇮🇪",
    continent: "europa",
    scholarship: "Government of Ireland Scholarships",
  },
  { id: "italia", name: "Itália", flag: "🇮🇹", continent: "europa", scholarship: "Study in Italy" },
  { id: "japao", name: "Japão", flag: "🇯🇵", continent: "asia", scholarship: "MEXT Scholarship" },
  {
    id: "malasia",
    name: "Malásia",
    flag: "🇲🇾",
    continent: "asia",
    scholarship: "Malaysia International Scholarship",
  },
  {
    id: "noruega",
    name: "Noruega",
    flag: "🇳🇴",
    continent: "europa",
    scholarship: "Study in Norway",
  },
  {
    id: "portugal",
    name: "Portugal",
    flag: "🇵🇹",
    continent: "europa",
    scholarship: "Study in Portugal",
  },
  {
    id: "reinounido",
    name: "Reino Unido",
    flag: "🇬🇧",
    continent: "europa",
    scholarship: "Chevening e Commonwealth Scholarships",
  },
  {
    id: "singapura",
    name: "Singapura",
    flag: "🇸🇬",
    continent: "asia",
    scholarship: "NUS Global Merit Scholarship",
  },
  {
    id: "suecia",
    name: "Suécia",
    flag: "🇸🇪",
    continent: "europa",
    scholarship: "Swedish Institute Scholarships",
  },
  {
    id: "suica",
    name: "Suíça",
    flag: "🇨🇭",
    continent: "europa",
    scholarship: "Swiss Government Excellence Scholarships",
  },
];

const guides: CountryGuide[] = guideData.map((country) => {
  const description = `Explore oportunidades de estudo em ${country.name} e planeje sua candidatura.`;
  const sections = countryGuideContent[country.id] ?? [
    {
      title: "Planejamento da candidatura",
      paragraphs: [
        "Compare os requisitos oficiais de cada instituição, incluindo proficiência linguística, documentos acadêmicos, prazos e custos.",
        "Regras de visto, trabalho e bolsas mudam; confirme-as em fontes governamentais e institucionais oficiais.",
      ],
      links: [],
    },
  ];
  return CountryGuideSchema.parse({
    ...country,
    description,
    highlights: [
      `Pesquise ${country.scholarship} e confirme os editais vigentes.`,
      "Verifique idioma, requisitos de admissão e custos junto à universidade.",
    ],
    sections,
  });
});

export function listCountryGuides() {
  return CountryGuideListResponseSchema.parse({
    items: guides.map(({ id, name, flag, continent, description, highlights }) => ({
      id,
      name,
      flag,
      continent,
      description,
      highlights,
    })),
  });
}

export function findCountryGuide(id: string): CountryGuide | undefined {
  return guides.find((guide) => guide.id === id);
}

export function countryGuideIds(): string[] {
  return guides.map(({ id }) => id);
}
