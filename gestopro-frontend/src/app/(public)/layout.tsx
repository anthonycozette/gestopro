import Navbar from "../../components/navbar";
import Footer from "../../components/footer";

export default function PublicLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950">
      <Navbar />
      <main className="pt-16">{children}</main>
      <Footer />
    </div>
  );
}
