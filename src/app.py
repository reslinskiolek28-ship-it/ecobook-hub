from pathlib import Path

import pandas as pd
import plotly.express as px
import streamlit as st

from db import (
    DB_PATH,
    add_listing,
    delete_listing,
    get_cities,
    get_connection,
    get_global_stats,
    get_powiaty,
    get_school_listings,
    get_schools,
    get_voivodeships,
    mark_listing_sold,
)

st.set_page_config(page_title='EcoBook Hub', layout='wide', initial_sidebar_state='collapsed')

st.markdown('''
<style>
    :root {
        --light-green: #39FF14;
        --dark-green: #006400;
        --mid-green: #228B22;
        --yellow: #FFFF00;
        --accent: #FFFFFF;
        --dark-bg: #070707;
        --panel-bg: #111111;
    }
    .stApp { background-color: var(--dark-bg); color: #E0E0E0; }
    [data-testid="collapsedControl"] { display: none; }
    section[data-testid="stSidebar"] { display: none; }
    h1, h2, h3 { color: var(--light-green) !important; font-weight: 900 !important; text-transform: uppercase; letter-spacing: 1px; }
    .metric-card { background-color: var(--panel-bg); border-top: 3px solid var(--mid-green); padding: 20px; border-radius: 4px; text-align: center; box-shadow: 0 4px 15px rgba(34,139,34,0.1); margin-bottom: 20px; }
    .metric-value { font-size: 34px; font-weight: 900; color: var(--light-green); }
    .metric-label { font-size: 13px; color: #AAA; text-transform: uppercase; letter-spacing: 1px; margin-top: 5px; }

    button[kind="primary"] { background-color: var(--light-green) !important; color: #000 !important; border: 1px solid var(--light-green) !important; font-weight: bold; }
    button[kind="primary"]:hover { background-color: var(--yellow) !important; border: 1px solid var(--yellow) !important; }
    button[kind="secondary"] { background-color: transparent !important; color: var(--light-green) !important; border: 1px solid var(--light-green) !important; font-weight: bold; }
    button[kind="secondary"]:hover { background-color: var(--dark-green) !important; color: #FFF !important; }

    .stTextInput>div>div>input, .stSelectbox>div>div>div, .stNumberInput>div>div>input { background-color: #1A1A1A !important; color: #FFF !important; border: 1px solid #333 !important; border-radius: 0px !important; }
</style>
''', unsafe_allow_html=True)

def ensure_database():
    db_path = Path(DB_PATH)
    if not db_path.exists():
        return False
    try:
        import duckdb

        con = duckdb.connect(str(db_path), read_only=True)
        con.execute("SELECT 1").fetchone()
        con.close()
        return True
    except Exception:
        try:
            db_path.unlink()
        except OSError:
            pass
        return False


try:
    if not ensure_database():
        raise RuntimeError("database generation failed")
    con = get_connection()
except Exception as e:
    st.error(f"Brak bazy danych: {e}. Uruchom: python scripts/download_data.py (wymaga internetu).")
    st.stop()

if 'page' not in st.session_state:
    st.session_state.page = "RAPORT"

def go_to_raport():
    st.session_state.page = "RAPORT"

def go_to_gielda():
    st.session_state.page = "GIELDA"

c_nav1, c_nav2 = st.columns(2)
with c_nav1:
    st.button("RAPORT BIZNESOWY I DANE SYSTEMOWE", on_click=go_to_raport, use_container_width=True, type="primary" if st.session_state.page == "RAPORT" else "secondary")
with c_nav2:
    st.button("GIEŁDA LOKALNA (WYSZUKIWARKA)", on_click=go_to_gielda, use_container_width=True, type="primary" if st.session_state.page == "GIELDA" else "secondary")

st.markdown("<br>", unsafe_allow_html=True)

if st.session_state.page == "RAPORT":
    st.title("ECOBOOK HUB // TERMINAL ANALITYCZNY")
    st.markdown("<h3 style='color: #228B22 !important; font-size: 1.2rem;'>BAZA MINISTERSTWA EDUKACJI NARODOWEJ (RSPO)</h3>", unsafe_allow_html=True)
    st.markdown("---")

    try:
        total_high_schools, total_listings, df_woj, df_types, df_powiaty = get_global_stats(con)
    except Exception:
        total_high_schools, total_listings, df_woj, df_types, df_powiaty = 0, 0, pd.DataFrame(), pd.DataFrame(), pd.DataFrame()

    c1, c2, c3 = st.columns(3)
    with c1:
        st.markdown(f'<div class="metric-card"><div class="metric-value">{total_high_schools}</div><div class="metric-label">Zindeksowanych Szkół Średnich (PL)</div></div>', unsafe_allow_html=True)
    with c2:
        st.markdown(f'<div class="metric-card"><div class="metric-value">~ {(total_high_schools * 2.5):,.0f} t</div><div class="metric-label">Roczne Szacunkowe Marnotrawstwo Papieru</div></div>', unsafe_allow_html=True)
    with c3:
        st.markdown(f'<div class="metric-card"><div class="metric-value" style="color: #FFF;">{total_listings}</div><div class="metric-label">Aktywnych Ofert Odsprzedaży P2P</div></div>', unsafe_allow_html=True)

    st.markdown("---")

    col_left, col_right = st.columns([1, 1])
    with col_left:
        st.markdown('''
        <div style="margin-bottom: 25px;">
            <span style="color: #39FF14; font-size: 1.4rem; font-weight: 900; display: block; margin-bottom: 8px;">Problem Rynkowy</span>
            <span style="color: #E0E0E0; font-size: 1rem; line-height: 1.6;">Coroczny zakup nowych podręczników szkolnych generuje bardzo wysokie koszty dla domowych budżetów, a stare książki często zalegają w domach lub trafiają na makulaturę. Ponadto tradycyjne handlowanie używanymi książkami w internecie jest czasochłonne, a wysokie koszty przesyłek kurierskich często przewyższają wartość samych podręczników. Nasz pomysł rozwiązuje te problemy poprzez cyfryzację i lokalizację całego procesu, oferując szybką wymianę książek bezpośrednio w szkole.</span>
        </div>
        <div style="margin-bottom: 25px;">
            <span style="color: #39FF14; font-size: 1.4rem; font-weight: 900; display: block; margin-bottom: 8px;">EcoBook Hub - Rozwiązanie</span>
            <span style="color: #E0E0E0; font-size: 1rem; line-height: 1.6;">EcoBook Hub to innowacyjna platforma webowa stworzona z myślą o uczniach i rodzicach, którzy co roku zmagają się z problemem wysokich kosztów zakupu podręczników. System ten całkowicie automatyzuje proces odkupowania i sprzedawania używanych książek szkolnych w obrębie jednej placówki lub regionu. Dzięki funkcji „odbioru w szkole” platforma całkowicie eliminuje koszty oraz czasochłonność tradycyjnych przesyłek kurierskich. Projekt aktywnie wspiera gospodarki obiegu zamkniętego, zmniejszając ilość marnowanego papieru i promowanie ekologicznych postaw wśród młodzieży. Wprowadzenie tego rozwiązania przynosi wymierne oszczędności domowe, a jednocześnie uczy uczniów praktycznego zarządzania zasobami i przedsiębiorczości.</span>
        </div>
        <div style="margin-bottom: 25px;">
            <span style="color: #39FF14; font-size: 1.4rem; font-weight: 900; display: block; margin-bottom: 8px;">Innowacyjność Projektu</span>
            <span style="color: #E0E0E0; font-size: 1rem; line-height: 1.6;">Wyjątkowość naszego projektu polega na całkowitej lokalizacji całego procesu wymiany i eliminacji kosztów wysyłki dzięki opcji odbioru książek bezpośrednio na terenie szkoły. Ponadto łączymy funkcję tradycyjnego marketplace'u z elementami ekologii i ekonomii współdzielenia, tworząc zamknięty, bezpieczny obieg podręczników dla szkolnej społeczności.</span>
        </div>
        ''', unsafe_allow_html=True)
    with col_right:
        st.markdown('''
        <div style="margin-bottom: 25px;">
            <span style="color: #39FF14; font-size: 1.4rem; font-weight: 900; display: block; margin-bottom: 8px;">Odbiorcy Docelowi</span>
            <span style="color: #E0E0E0; font-size: 1rem; line-height: 1.6;">Z głównego rozwiązania będą korzystać uczniowie szkół ponadpodstawowych oraz ich rodzice, którzy poszukują tańszych i ekologicznych sposobów na zdobycie wymaganych książek. Dodatkowo z platformy skorzystają same szkoły, zyskując sprawne narzędzie wspierające lokalną społeczność i promocję idei ekologicznych.</span>
        </div>
        <div style="margin-bottom: 25px;">
            <span style="color: #39FF14; font-size: 1.4rem; font-weight: 900; display: block; margin-bottom: 8px;">Stos Technologiczny i Źródła</span>
            <span style="color: #E0E0E0; font-size: 1rem; line-height: 1.6;">Architektura opiera się na Otwartych Danych. Skrypty automatycznie integrują się z API <b>dane.gov.pl</b> (zbiór 839, licencja CC BY 4.0). Całość napędza Python (Pandas) oraz osadzony silnik analityczny baz danych <b>DuckDB</b>, pracujący w pamięci. Interfejs i interaktywne wizualizacje obsługują biblioteki Streamlit oraz Plotly Express.</span>
        </div>
        <div style="margin-bottom: 25px;">
            <span style="color: #39FF14; font-size: 1.4rem; font-weight: 900; display: block; margin-bottom: 8px;">Wizja Przyszłości</span>
            <span style="color: #E0E0E0; font-size: 1rem; line-height: 1.6;">W perspektywie długoterminowej planujemy przekształcić platformę w pełni funkcjonalną aplikację mobilną zintegrowaną z systemami szkolnymi, co ułatwi uczniom szybki handel książkami za pomocą kilku kliknięć. W przyszłości zamierzamy także rozbudować serwis o automatyczny system rekomendacji lektur oraz moduł wymiany innych materiałów edukacyjnych, takich jak notatki czy atlasy. Dzięki temu projekt zyska ogólnokrajową skalę, realnie wspierając ekologię i oszczędności w tysiącach szkół w całej Polsce.</span>
        </div>
        ''', unsafe_allow_html=True)

    st.markdown("---")
    st.markdown("#### STATYSTYKI SEKTORA OŚWIATY (DANE RSPO, MEN)")

    try:
        w1, w2 = st.columns(2)
        with w1:
            fig1 = px.bar(df_woj, x='wojewodztwo', y='zuzycie_ton', title='Szacowane roczne marnotrawstwo papieru edukacyjnego (Tony)', color_discrete_sequence=['#39FF14'])
            fig1.update_layout(plot_bgcolor='rgba(0,0,0,0)', paper_bgcolor='rgba(0,0,0,0)', font_color='#FFF', xaxis_title="", yaxis_title="Tony Papieru")
            st.plotly_chart(fig1, use_container_width=True)

        with w2:
            custom_colors = ['#006400', '#228B22', '#39FF14', '#FFFF99', '#FFFF00', '#DAA520']
            fig2 = px.pie(df_types, values='count', names='typ', title='Struktura szkół średnich w bazie', color_discrete_sequence=custom_colors, hole=0.4)
            fig2.update_layout(plot_bgcolor='rgba(0,0,0,0)', paper_bgcolor='rgba(0,0,0,0)', font_color='#FFF')
            st.plotly_chart(fig2, use_container_width=True)

        st.markdown("<br>", unsafe_allow_html=True)

        fig3 = px.bar(df_powiaty.sort_values(by='liczba_szkol', ascending=True), y='powiat', x='liczba_szkol', orientation='h', title='Top 10 Powiatów z największą liczbą placówek oświatowych', color_discrete_sequence=['#39FF14'])
        fig3.update_layout(plot_bgcolor='rgba(0,0,0,0)', paper_bgcolor='rgba(0,0,0,0)', font_color='#FFF', yaxis_title="", xaxis_title="Liczba Szkół Średnich", height=400)
        st.plotly_chart(fig3, use_container_width=True)
    except Exception:
        pass

    st.markdown("---")
    st.button("➡️ PRZEJDŹ DO GIEŁDY LOKALNEJ (Wyszukaj placówkę i wymień książki)", on_click=go_to_gielda, use_container_width=True, type="primary")

elif st.session_state.page == "GIELDA":
    st.title("GIEŁDA LOKALNA // WYSZUKIWARKA")
    st.markdown("Wybierz lokalizację, a następnie wpisz nazwę placówki lub ulicę.")

    col_w, col_p, col_m, col_s = st.columns([1, 1, 1, 2])
    with col_w:
        voivodeships = get_voivodeships(con)
        sel_woj = st.selectbox("1. Województwo", voivodeships if voivodeships else ["Brak"])
    with col_p:
        powiaty = get_powiaty(con, sel_woj) if sel_woj != "Brak" else []
        sel_powiat = st.selectbox("2. Powiat", powiaty if powiaty else ["Brak"])
    with col_m:
        cities = get_cities(con, sel_powiat) if sel_powiat != "Brak" else []
        sel_city = st.selectbox("3. Miasto", cities if cities else ["Brak"])
    with col_s:
        schools = get_schools(con, sel_city) if sel_city != "Brak" else []
        school_dict = {s[1]: str(s[0]) for s in schools}
        sel_school_name = st.selectbox("4. Szkoła Średnia (Wyszukaj wpisując ulicę lub nazwę)", list(school_dict.keys()) if schools else ["Brak placówek"])
        sel_school_id = school_dict.get(sel_school_name, None)

    st.markdown("---")

    if sel_school_id:
        st.markdown(f"<h3 style='color: #39FF14 !important;'>AKTYWNA STREFA: {sel_school_name}</h3>", unsafe_allow_html=True)
        tab_list, tab_add = st.tabs(["[ POKAŻ GIEŁDĘ LOKALNĄ ]", "[ + WYSTAW SWÓJ PODRĘCZNIK ]"])

        with tab_list:
            listings = get_school_listings(con, sel_school_id)
            if not listings:
                st.warning("BAZA PUSTA. Brak aktywnych ofert na terenie tej placówki. Dodaj pierwszą książkę!")
            else:
                for listing in listings:
                    listing_id, title, subject, price, condition, pickup, contact, image, created_at, status = listing
                    with st.container():
                        st.markdown(f"""
                        <div style="border-left: 4px solid #39FF14; background-color: #111; padding: 15px; margin-bottom: 10px;">
                            <h4 style="color: #FFF !important; margin-bottom: 0; border: none; text-transform: none;">{title} <span style="color: #39FF14;">({price} PLN)</span></h4>
                            <p style="color: #888; font-size: 14px; margin-top: 5px;">
                                <b>Przedmiot:</b> {subject} | <b>Stan:</b> {condition} | <b>Punkt Odbioru:</b> {pickup}<br>
                                <b>Kontakt:</b> <span style="color: #FFF;">{contact}</span>
                            </p>
                        </div>
                        """, unsafe_allow_html=True)
                        if image:
                            st.image(image, width=250)
                        c_sold, c_delete = st.columns(2)
                        with c_sold:
                            if st.button("Oznacz jako sprzedane", key=f"sold_{listing_id}"):
                                mark_listing_sold(DB_PATH, listing_id)
                                st.rerun()
                        with c_delete:
                            if st.button("Usuń ofertę", key=f"delete_{listing_id}"):
                                delete_listing(DB_PATH, listing_id)
                                st.rerun()

        with tab_add:
            with st.form("new_offer", clear_on_submit=True):
                st.markdown("#### DEKLARACJA SPRZEDAŻY BEZPOŚREDNIEJ (P2P)")
                c1, c2 = st.columns(2)
                with c1:
                    title = st.text_input("Tytuł i Wydawnictwo")
                    subject = st.selectbox("Przedmiot", ["Matematyka", "J. Polski", "J. Angielski", "Fizyka", "Chemia", "Biologia", "Geografia", "Historia", "Informatyka", "Zawodowe", "Inne"])
                    price = st.number_input("Cena (PLN)", min_value=0.0, step=1.0)
                with c2:
                    condition = st.selectbox("Stan Książki", ["Jak Nowy", "Używany (Drobne Ślady)", "Używany (Pozakreślany)"])
                    pickup = st.text_input("Gdzie w szkole (np. Szafki przy sali gimnastycznej)")
                    contact = st.text_input("Kontakt (Tel / Messenger)")

                uploaded_file = st.file_uploader("Zdjęcie (opcjonalnie)", type=['png', 'jpg'])
                submit = st.form_submit_button(">> ZAINICJUJ OFERTĘ W SZKOLE <<")

                if submit:
                    if not title or not contact:
                        st.error("Pola Tytuł i Kontakt są absolutnie wymagane.")
                    else:
                        img_bytes = uploaded_file.getvalue() if uploaded_file else None
                        add_listing(DB_PATH, sel_school_id, title, subject, price, condition, pickup, contact, img_bytes)
                        st.success("Wpis zindeksowany na giełdzie szkolnej.")
                        st.rerun()

    st.markdown("---")
    st.button("⬅️ WRÓĆ DO RAPORTU I WIZUALIZACJI BIZNESOWYCH", on_click=go_to_raport, use_container_width=True, type="primary")
